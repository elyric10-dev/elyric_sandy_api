<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GlobalSettings;
use App\Models\Table;
use App\Models\Kids;
use Illuminate\Support\Facades\Validator;
use App\Models\AttendingGuest;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class TableController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tables = Table::all();

        foreach ($tables as $key => $table) {
            $kidsCount = Kids::where('table_id', $table->id)->count();
            $tables[$key]['key'] = $table->id;
            $tables[$key]['table_guests_count'] = AttendingGuest::where('table_id', $table->id)->count() + $kidsCount;
        }

        $tables = $tables->sortBy('table_number')->values()->toArray();

        $attendingGuests = AttendingGuest::whereNotNull('table_id')->get();
        $attendingGuests = collect($attendingGuests)->groupBy('table_id');

        $kids = Kids::whereNotNull('table_id')->get();
        foreach ($kids as $kid) {
            $kid['is_kid'] = true;
            $kid['lastname'] = $kid->lastname . ' (Kids)';
        }
        
        $kids = collect($kids)->groupBy('table_id');


        $tableGuests = collect($tables)->map(function ($table) use ($attendingGuests, $kids) {
            $tableId = $table['id'];
            
            $guestsForTable = $attendingGuests->get($tableId, collect())->map(function ($guest) {
                $guest['is_kid'] = false;
                return $guest;
            });
            
            $kidsForTable = $kids->get($tableId, collect())->map(function ($kid) {
                $kid['is_kid'] = true;
                return $kid;
            });
            
            return [
                'table_id' => $tableId,
                'members' => $guestsForTable->merge($kidsForTable)
            ];
        });
    
        return response()->json([
            'tables' => $tables,
            'tables_guests' => $tableGuests,
        ]);
    }

    
    public function show($table_id)
    {
        $attendingGuests = AttendingGuest::where('table_id', $table_id)->get();
        $kids = Kids::where('table_id', $table_id)->get();

        foreach ($attendingGuests as $key => $attendingGuest) {
            $attendingGuests[$key]['key'] = $attendingGuest->id;
        }

        foreach($kids as $key => $kid){
            $kids[$key]['key'] = $kid->id . '-kid';
            $kids[$key]['is_kid'] = true;

            $attendingGuests[] = $kid;
        }

        return response()->json([
            'attendingGuests' => $attendingGuests
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(GlobalSettings::first()->is_locked) {
            return response()->json([
                'error' => 'RSVP is locked'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'table_number' => 'required|integer|min:1|unique:tables',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|string|max:255',
        ]);

        // error if not unique
        if ($validator->errors()->has('table_number')) {
            return response()->json([
                'error' => 'Table number already exists'
            ], 422);
        }
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $table = Table::create([
            'table_number' => $request->table_number,
            'capacity' => $request->capacity,
            'status' => $request->status,
        ]);

        $tables = $this->index();

        

        $attendingGuests = AttendingGuest::whereNotNull('table_id')->get();
        $attendingGuests = collect($attendingGuests)->groupBy('table_id');

        return response()->json([
            'message' => 'Table created successfully',
            'table' => $table,
            'tables' => $tables->original['tables'],
            'tables_guests' => $attendingGuests,
        ]);
    }

    public function destroy($id) {
        if(GlobalSettings::first()->is_locked) {
            return response()->json([
                'error' => 'RSVP is locked'
            ], 403);
        }

        $table = Table::find($id);
        if(!$table) {
            return response()->json([
                'error' => 'Table not found'
            ], 404);
        }

        $attendingGuests = AttendingGuest::where('table_id', $id)->get();

        foreach($attendingGuests as $attendingGuest) {
            $attendingGuest->table_id = null;
            $attendingGuest->save();
        }

        $table->delete();

        $tables = $this->index();

        return response()->json([
            'message' => 'Table deleted successfully',
            'tables' => $tables->original['tables']
        ]);
    }

    public function tablesGuests()
    {
        // Get the tables and guests
        $tables = Table::all()->sortBy('table_number');

        $kids = Kids::whereNotNull('table_id')->get();

        foreach ($kids as $kid) {
            $kid['is_kid'] = true;
            $kid['lastname'] = $kid->lastname . ' (Kids)';
        }
        $kids = collect($kids)->groupBy('table_id');
        
        $tablesGuests = $tables->mapWithKeys(function ($table) {
            $guests = AttendingGuest::where('table_id', $table->id)
                ->get();
            
            return [$table->id => $guests];
        });

        foreach ($kids as $tableId => $kidsList) {
            if ($tablesGuests->has($tableId)) {
                // Merge kids into existing table guests
                $tablesGuests[$tableId] = $tablesGuests[$tableId]->merge($kidsList);
            } else {
                // If table_id doesn't exist in tablesGuests, create a new entry
                $tablesGuests[$tableId] = $kidsList;
            }
        }
    
        // Create a new Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Determine the maximum number of guests in any table
        $maxGuestCount = 0;
        foreach ($tablesGuests as $guests) {
            $maxGuestCount = max($maxGuestCount, count($guests));
        }

        // Add numbering column (Column A)
        $sheet->setCellValue('A1', '#');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        for ($i = 1; $i <= $maxGuestCount; $i++) {
            $sheet->setCellValue('A' . ($i + 1), $i);
            $sheet->getStyle('A' . ($i + 1))->getFont()->setBold(true)->setSize(13);
            $sheet->getStyle('A' . ($i + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }
        $sheet->getColumnDimension('A')->setWidth(5); // Adjust width for numbering

        $columnIndex = 'B'; // Start tables from column B

        foreach ($tables as $table) {
            $rowIndex = 1; // Reset row index for each table

            // Set table header
            $sheet->setCellValue($columnIndex . $rowIndex, 'Table #' . $table->table_number);
            
            // Apply bold style to header
            $sheet->getStyle($columnIndex . $rowIndex)->getFont()->setBold(true)->setSize(14);

            $rowIndex++; // Move to next row

            // Populate guest names under each table
            if (!empty($tablesGuests[$table->id])) {
                foreach ($tablesGuests[$table->id] as $guest) {
                    $sheet->setCellValue($columnIndex . $rowIndex, $guest->name . ' ' . $guest->middle . ' ' . $guest->lastname);
                    $sheet->getStyle($columnIndex . $rowIndex)->getFont()->setBold(false)->setSize(13);
                    $rowIndex++;
                }
            }

            // Increase column width manually for better spacing
            $sheet->getColumnDimension($columnIndex)->setWidth(30);

            $columnIndex++; // Move to the next column (C, D, etc.)
        }
    
        // Prepare the file for download
        $writer = new Xlsx($spreadsheet);
        
        $filename = now()->format('Y-m-d-H-i-s');
        
        // Output file paths and URLs
        $directory = storage_path('app/public/exports/tables-guests/');
        $file_dl_full_path = $directory . $filename . '.xlsx';

        // Ensure directory exists, create if not found
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Save the spreadsheet to a file
        $writer = new Xlsx($spreadsheet);
        // ob_end_clean(); // Clean output buffer
        $writer->save($file_dl_full_path);

        // $file_sheet = array(
        //     // 'dl_file' => $file_dl_full_path,
        //     'url_path' => url('storage/exports/tables-guests/' . $filename . '.xlsx'),
        // );

        return response()->json([
            'url_path' => url('storage/exports/tables-guests/' . $filename . '.xlsx'),
            'kids' => $kids,
            'tablesGuests' => $tablesGuests,
        ]);

    }
}
