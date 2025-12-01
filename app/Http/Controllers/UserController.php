<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index() {
        return response()->json(User::all());
    }

    // admin Add user
    public function storeStaff(Request $request) {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6'
        ]);

        $staff = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'staff'
        ]);

        return response()->json([
            'message' => 'Staff created successfully',
            'data' => $staff
        ], 201);
    }

    // Update role atau data user (admin)
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'  => 'nullable',
            'email' => 'nullable|email|unique:users,email,' . $id,
            'role'  => 'nullable|in:admin,staff,user',
        ]);

        $user->update($request->all());
        return response()->json($user);
    }

    // Hapus user (admin)
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // optional: mencegah admin menghapus admin lain
        if ($user->role === 'admin') {
            return response()->json(['message' => 'Forbidden to delete another admin'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }
}
