<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;

class RoleController extends Controller
{
        /**
     * Display a listing of the roles.
     */
    public function index()
    {
        $roles = Role::all();
        return response()->json([
            'status' => true,
            'data' => $roles
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'type' => 'required|string|max:255',
            'permissions' => 'nullable|array'
        ]);

        $role = Role::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Role created successfully',
            'data' => $role
        ], 201);
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role)
    {
        return response()->json([
            'status' => true,
            'data' => $role
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string|max:255',
            'type' => 'string|max:255',
            'permissions' => 'nullable|array'
        ]);

        $role->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Role updated successfully',
            'data' => $role
        ]);
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role)
    {
        $role->delete();

        return response()->json([
            'status' => true,
            'message' => 'Role deleted successfully'
        ], 201);
    }
}
