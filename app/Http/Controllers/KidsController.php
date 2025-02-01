<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kids;
use App\Models\GlobalSettings;

class KidsController extends Controller
{
    // api
    public function index()
    {
        $kids = Kids::all();

        if(count($kids) == 0){
            return response()->json([
                'message' => 'No kids found',
                'status' => 404,
            ]);
        }
        return response()->json([
            'message' => 'Kids retrieved successfully',
            'kids' => $kids,
        ]);
    }

    public function show($id)
    {
        $kid = Kids::find($id);
        if(!$kid){
            return response()->json([
                'message' => 'Kid not found',
                'status' => 404,
            ]);
        }
        return response()->json([
            'message' => 'Kid retrieved successfully',
            'kid' => $kid,
        ]);
    }

    public function store(Request $request)
    {
        if(GlobalSettings::first()->is_locked){
            return response()->json([
                'message' => 'Global settings are locked',
                'status' => 403,
            ]);
        }

        $validation = $request->validate([
            'name' => 'required',
            'lastname' => 'required',
            'invitation_id' => 'required',
            'party_member_id' => 'required',
            'is_attending' => 'nullable|boolean',
        ]);

        if ($validation) {
            $kids = Kids::create($validation);
        }

        return response()->json([
            'message' => 'Kids created successfully',
            'kids' => $kids,
        ]);
    }

    public function update(Request $request, $id)
    {
        $kids = Kids::find($id);

        if(GlobalSettings::first()->is_locked){
            return response()->json([
                'message' => 'Global settings are locked',
                'status' => 403,
            ]);
        }

        $kids->update($request->all());
        return response()->json([
            'message' => 'Kids updated successfully',
            'kids' => $kids,
        ]);
    }

    public function destroy($id)
    {
        $kid = Kids::find($id);

        if(GlobalSettings::first()->is_locked){
            return response()->json([
                'message' => 'Global settings are locked',
                'status' => 403,
            ]);
        }

        $kid->delete();
        return response()->json([
            'message' => 'Kid deleted successfully',
            'kid' => $kid,
        ]);
    }

}
