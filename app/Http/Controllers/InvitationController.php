<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\PartyMember;
use App\Models\GlobalSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class InvitationController extends Controller
{
    public function show(string $code): JsonResponse
    {
        $invitation = Invitation::with('partyMembers')
            ->where('invitation_code', $code)
            ->firstOrFail();

        return response()->json([
            'invitation' => $invitation
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
            'is_attending' => 'required|boolean',
            'party_members' => 'required|array|min:1',
            'party_members.*.name' => 'nullable|string|max:255',
            'party_members.*.middle' => 'nullable|string|max:255',
            'party_members.*.lastname' => 'nullable|string|max:255',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Verify party members count doesn't exceed seat count
        if (count($request->party_members) > $invitation->seat_count) {
            return response()->json([
                'error' => 'Party members exceed allowed seat count of ' . $invitation->seat_count
            ], 422);
        }
    
        // Update invitation
        $invitation->is_attending = $request->is_attending;
        $invitation->save();

        // Update party members
        $existingPartyMembers = $invitation->partyMembers;
        $updatedPartyMembers = [];

        foreach ($request->party_members as $key => $member) {
            if ($key < $existingPartyMembers->count()) {
                // Update existing party member
                $partyMember = $existingPartyMembers[$key];
                $partyMember->name = $member['name'];
                $partyMember->middle = $member['middle'] ?? null;
                $partyMember->lastname = $member['lastname'];
                $partyMember->save();
                $updatedPartyMembers[] = $partyMember;
            } else {
                // Create new party member
                $partyMember = PartyMember::create([
                    'invitation_id' => $invitation->id,
                    'name' => $member['name'] ?? null,
                    'middle' => $member['middle'] ?? null,
                    'lastname' => $member['lastname'] ?? null
                ]);
                $updatedPartyMembers[] = $partyMember;
            }
    }
    
        return response()->json([
            'message' => 'RSVP updated successfully',
            'invitation' => $invitation->load('partyMembers')
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
            'name' => 'required|string|max:255',
            'middle' => 'nullable|string|max:255',
            'lastname' => 'required|string|max:255',
            'seat_count' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $invitation = Invitation::create([
            'name' => $request->name,
            'middle' => $request->middle,
            'lastname' => $request->lastname,
            'seat_count' => $request->seat_count,
            'invitation_code' => Invitation::generateInvitationCode(),
        ]);

        return response()->json([
            'message' => 'Invitation created successfully',
            'invitation' => $invitation,
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