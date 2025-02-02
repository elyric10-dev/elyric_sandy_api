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


     public function show($user_id)
     {
         $attendingGuest = AttendingGuest::where('id', $user_id)->get();
 
         return response()->json([
             'attendingGuests' => $attendingGuest
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

        $table_members = AttendingGuest::where('table_id', $request->table_id)->get();
 
        return response()->json([
            'message' => 'Attending Guest added successfully',
            'attendingGuests' => $attendingGuests,
            'table_members' => $table_members
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
         
        $table_members = AttendingGuest::where('table_id', $request->table_id)->get();
 
         return response()->json([
             'message' => 'Attending Guest deleted on table successfully',
             'table_members' => $table_members
         ]);
     }
     


}
