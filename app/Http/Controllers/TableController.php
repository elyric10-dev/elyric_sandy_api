<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GlobalSettings;
use App\Models\Table;
use Illuminate\Support\Facades\Validator;
use App\Models\AttendingGuest;

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
            $tables[$key]['key'] = $table->id;
        }

        $tables = $tables->sortBy('table_number')->values()->toArray();

        return response()->json([
            'tables' => $tables
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

        return response()->json([
            'message' => 'Table created successfully',
            'table' => $table,
            'tables' => $tables->original['tables'] 
        ]);
    }

    public function destroy($id) {
        $table = Table::find($id);
        if(!$table) {
            return response()->json([
                'error' => 'Table not found'
            ], 404);
        }

        $table->delete();

        $tables = $this->index();

        return response()->json([
            'message' => 'Table deleted successfully',
            'tables' => $tables->original['tables']
        ]);
    }
        
}
