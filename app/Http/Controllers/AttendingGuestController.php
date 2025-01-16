<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendingGuest;
use Illuminate\Support\Facades\Validator;

class AttendingGuestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

     public function index()
     {
         $attendingGuests = AttendingGuest::whereNull('table_id')->get();


         foreach ($attendingGuests as $key => $attendingGuest) {
             $attendingGuests[$key]['key'] = $attendingGuest->id;
         }

         return response()->json([
             'attendingGuests' => $attendingGuests
         ]);
     }


     public function show($table_id)
     {
         $attendingGuests = AttendingGuest::where('table_id', $table_id)->get();
 
         foreach ($attendingGuests as $key => $attendingGuest) {
             $attendingGuests[$key]['key'] = $attendingGuest->id;
         }
 
         return response()->json([
             'attendingGuests' => $attendingGuests
         ]);
     }

    // ADD GUEST TO TABLE
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'table_id' => 'required|integer|exists:tables,id',
            'attending_guest_ids' => 'required|exists:attending_guests,id'
        ]);
 
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $attendingGuests = AttendingGuest::whereIn('id', $request->attending_guest_ids)->get();

        foreach ($attendingGuests as $key => $attendingGuest) {
            $attendingGuest->table_id = $request->table_id;
            $attendingGuest->save();
            $attendingGuests[$key]['key'] = $attendingGuest->id;
        }
 
        return response()->json([
            'message' => 'Attending Guest added successfully',
            // 'attendingGuests' => $this->show($request->table_id)->original['attendingGuests']
            'attendingGuests' => $attendingGuests
        ]);
    }

     public function destroy(Request $request, $id)
     {
        $validator = Validator::make($request->all(), [
            'table_id' => 'required|integer|exists:tables,id',
        ]);
 
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

         $attendingGuest = AttendingGuest::find($id);
         if($attendingGuest->table_id === null) {
             return response()->json([
                 'error' => 'Attending Guest not found'
             ], 404);
         }
 
         $attendingGuest->table_id = null;
         $attendingGuest->save();
         $attendingGuests = $this->show($request->table_id);
 
         return response()->json([
             'message' => 'Attending Guest deleted on table successfully',
             'attendingGuests' => $attendingGuests->original['attendingGuests']
         ]);
     }
     


}
