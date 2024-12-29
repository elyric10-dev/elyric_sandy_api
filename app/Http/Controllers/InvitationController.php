<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\PartyMember;
use App\Models\AttendingGuest;
use App\Models\Guest;
use App\Models\GlobalSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class InvitationController extends Controller
{
    public function show(string $code): JsonResponse
    {
        $invitation = Invitation::with('guests')
            ->where('invitation_code', $code)
            ->first();
        
        if(!$invitation) {
            return response()->json([
                'error' => 'Invitation not found'
            ], 404);
        }

        return response()->json([
            'invitation' => $invitation
        ]);
    }
    public function showAttendingGuests(string $code): JsonResponse
    {
        $invitation_code = Invitation::where('invitation_code', $code)->first();
        if(!$invitation_code) {
            return response()->json([
                'error' => 'Invitation not found'
            ], 404);
        }

        $invitation_code_id = $invitation_code->id;
        $attending_guests = AttendingGuest::where('invitation_id', $invitation_code_id)->get();
        
        if(!$attending_guests) {
            return response()->json([
                'error' => 'Invitation not found'
            ], 404);
        }

        return response()->json([
            'attending_guests' => $attending_guests
        ]);
    }

    public function rsvp(Request $request, string $code): JsonResponse
    {
        if(GlobalSettings::first()->is_locked) {
            return response()->json([
                'error' => 'RSVP is locked'
            ], 403);
        }

        $invitation = Invitation::where('invitation_code', $code)->first();
        if(!$invitation) {
            return response()->json([
                'error' => 'Invitation not found'
            ], 404);
        }


        $validator = Validator::make($request->all(), [
            'party_members.*.name' => 'nullable|string|max:255',
            'party_members.*.middle' => 'nullable|string|max:255',
            'party_members.*.lastname' => 'nullable|string|max:255',
            'party_members.*.is_attending' => 'nullable|boolean',
            'party_members.*.replacement_name' => 'nullable|string|max:255',
            'party_members.*.replacement_middle' => 'nullable|string|max:255',
            'party_members.*.replacement_lastname' => 'nullable|string|max:255',
            'party_members.*.replacement_is_attending' => 'nullable|boolean',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Verify party members count doesn't exceed seat count
        if (count($request->party_members) > $invitation->seat_count) {
            return response()->json([
                'error' => 'Party members exceed allowed seat count of ' . $invitation->seat_count . ' only'
            ], 422);
        }

        $existingPartyMembers = $invitation->guests;

        foreach ($request->party_members as $key => $member) {
            if ($key < $existingPartyMembers->count()) {
                $partyMember = $existingPartyMembers[$key];

                if($member['is_attending'] && !AttendingGuest::where('invitation_id', $invitation->id)->where('guest_id', $member['id'])->exists()){
                    $attendingGuest = AttendingGuest::create([
                        'invitation_id' => $invitation->id,
                        'party_member_id' => $member['party_member_id'],
                        'guest_id' => $member['id'],
                        'name' => $member['name'] ?? null,
                        'middle' => $member['middle'] ?? null,
                        'lastname' => $member['lastname'] ?? null,
                    ]);

                    // Update guests table is_attending
                    $guest = Guest::where('invitation_id', $invitation->id)
                        ->where('party_member_id', $member['party_member_id'])
                        ->where('id', $member['id'])
                        ->first();
                    $guest->is_attending = $member['is_attending'];
                    $guest->save();
                }
                else if(!$member['is_attending']) {
                    // Delete if guest is not attending
                    AttendingGuest::where('invitation_id', $invitation->id)
                        ->where('guest_id', $member['id'])
                        ->delete();

                    // Update guests table is_attending
                    $guest = Guest::where('invitation_id', $invitation->id)
                        ->where('party_member_id', $member['party_member_id'])
                        ->where('id', $member['id'])
                        ->first();
                    $guest->is_attending = $member['is_attending'];
                    $guest->save();

                    
                }
            }
        }
    
        $attended_count = AttendingGuest::where('invitation_id', $invitation->id)->count();
        $invitation->attended_count = $attended_count;
        $invitation->save();
    
        return response()->json([
            'message' => 'RSVP updated successfully',
            'invitation' => $invitation->load('guests'),
            'attended_count' => $attended_count
        ]);
    }


    public function store(Request $request): JsonResponse
    {
        if(GlobalSettings::first()->is_locked) {
            return response()->json([
                'error' => 'RSVP is locked'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'seat_count' => 'required|integer|min:1',
            'party_members' => 'required|array|min:1',
            'party_members.*.name' => 'string|max:255',
            'party_members.*.middle' => 'nullable|string|max:255',
            'party_members.*.lastname' => 'string|max:255',
            'party_members.*is_attending' => 'boolean|default:false',
            'party_members.*.replacement_name' => 'nullable|string|max:255',
            'party_members.*.replacement_lastname' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $invitation_code = Invitation::generateInvitationCode();

        if (count($request->party_members) > $request->seat_count) {
            return response()->json([
                'error' => 'Party members exceed allowed seat count of ' . $request->seat_count . ' only'
            ], 422);
        }

        $invitation = Invitation::create([
            'seat_count' => $request->seat_count,
            'invitation_code' => $invitation_code,
        ]);

        $partyMember = PartyMember::create();
        $existingPartyMembers = $request->party_members;

        $guests = [];

        foreach ($existingPartyMembers as $member) {
            $guest = Guest::create([
                'invitation_id' => $invitation->id,
                'party_member_id' => $partyMember->id,
                'name' => $member['name'] ?? null,
                'middle' => $member['middle'] ?? null,
                'lastname' => $member['lastname'] ?? null,
                'is_attending' => $member['is_attending'] ?? null,
                'replacement_name' => $member['replacement_name'] ?? null,
                'replacement_middle' => $member['replacement_middle'] ?? null,
                'replacement_lastname' => $member['replacement_lastname'] ?? null,
                'replacement_is_attending' => $member['replacement_is_attending'] ?? null,
            ]);
            $guests[] = $guest;
        }
        return response()->json([
            'message' => 'Invitation created successfully',
            'invitation_link' => env('FRONTEND_URL') . '/rsvp/' . $invitation_code
        ], 201);
    }


    public function update(Request $request, string $code): JsonResponse
    {
        if(GlobalSettings::first()->is_locked) {
            return response()->json([
                'error' => 'RSVP is locked'
            ], 403);
        }

        $invitation = Invitation::where('invitation_code', $code)->first();
        if(!$invitation) {
            return response()->json([
                'error' => 'Invitation not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'middle' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'seat_count' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $invitation->name = $request->name ?? $invitation->name;
        $invitation->middle = $request->middle ?? $invitation->middle;
        $invitation->lastname = $request->lastname ?? $invitation->lastname;
        $invitation->seat_count = $request->seat_count ?? $invitation->seat_count;
        $invitation->is_attending = $request->is_attending ?? $invitation->is_attending;
        $invitation->save();

        return response()->json([
            'message' => 'Invitation updated successfully',
            'invitation' => $invitation
        ]);
    }

    //lock or unlock global settings, toogle is_locked
    public function lock(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'is_locked' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $global_settings = GlobalSettings::first();
        $global_settings->is_locked = $request->is_locked;
        $global_settings->save();

        return response()->json([
            'message' => 'Global settings updated successfully',
            'global_settings' => $global_settings
        ]);
    }
    

    public function testing(): JsonResponse
    {
        return response()->json([
            'message' => 'Testing endpoint'
        ]);
    }
}