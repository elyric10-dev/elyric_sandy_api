<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendingGuest;
use App\Models\GlobalSettings;
use App\Models\Kids;
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
         $kids = Kids::whereNull('table_id')->get();

         foreach ($attendingGuests as $key => $attendingGuest) {
             $attendingGuests[$key]['key'] = $attendingGuest->id;
         }

         foreach($kids as $key => $kid){
             $kids[$key]['key'] = $kid->id . '-kid';
             $kids[$key]['is_kid'] = true;
             $kids[$key]['lastname'] = $kid->lastname . ' (Kids)';

             $attendingGuests[] = $kid;
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
        if(GlobalSettings::first()->is_locked) {
            return response()->json([
                'error' => 'RSVP is locked'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'table_id' => 'required|integer|exists:tables,id',
            'selectedGuests' => 'array',
            'selectedKids' => 'array'
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $tableId = $request->table_id;
        $selectedGuests = $request->selectedGuests ?? [];
        $selectedKids = $request->selectedKids ?? [];
    
        // Process Guests
        foreach ($selectedGuests as $guest) {
            $attendingGuest = AttendingGuest::find($guest['id']);
            if ($attendingGuest) {
                $attendingGuest->table_id = $tableId;
                $attendingGuest->save();
            }
        }
    
        // Process Kids
        foreach ($selectedKids as $kid) {
            $kidRecord = Kids::find($kid['id']);
            if ($kidRecord) {
                $kidRecord->table_id = $tableId;
                $kidRecord->save();
            }
        }

        $attendingGuestsTableMembers = AttendingGuest::where('table_id', $tableId)->get();
        $kidsTableMembers = Kids::where('table_id', $tableId)->get();

        foreach($kidsTableMembers as $kid){
            $kid->lastname = $kid->lastname;
            $kid->save();
        }

        $tableMembers = $attendingGuestsTableMembers->merge($kidsTableMembers);
 
        return response()->json([
            'message' => 'Attending Guest added successfully',
            // 'attendingGuests' => $attendingGuests,
            'table_members' => $tableMembers
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


         if($request->is_kid) {
            $kid = Kids::find($id);
            if($kid->table_id === null) {
                return response()->json([
                    'error' => 'Kid not found'
                ], 404);
            }

             $kid->table_id = null;
             $kid->save();
         }else{
            $attendingGuest = AttendingGuest::find($id);
            if($attendingGuest->table_id === null) {
                return response()->json([
                    'error' => 'Attending Guest not found'
                ], 404);
            }

             $attendingGuest->table_id = null;
             $attendingGuest->save();
         }

         
         $guest_members = AttendingGuest::where('table_id', $request->table_id)->get();
         $kids_members = Kids::where('table_id', $request->table_id)->get();
         $tableMembers = $guest_members->map(function ($guest) {
             $guest->is_kid = false;
             return $guest;
         })->merge(
             $kids_members->map(function ($kid) {
                 $kid->is_kid = true;
                 return $kid;
             })
         );

         return response()->json([
             'message' => `Attending Guest deleted on table successfully`,
             'table_members' => $tableMembers,
         ]);
     }
     


}
