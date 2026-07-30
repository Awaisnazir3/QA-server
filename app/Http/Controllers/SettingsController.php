<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Display settings page
     */
    public function index()
    {
        $users = AdminUser::orderBy('id', 'desc')->get();

        return view('settings.index', [
            'users' => $users,
        ]);
    }

    /**
     * Add new admin user
     */
    public function addUser(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|unique:admin_users,username',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,operator',
        ]);

        AdminUser::create([
            'username' => $validated['username'],
            'password_hash' => password_hash($validated['password'], PASSWORD_BCRYPT),
            'role' => $validated['role'],
        ]);

        return redirect()->route('settings.index')
            ->with('success', 'User added successfully!');
    }

    /**
     * Delete admin user
     */
    public function deleteUser(AdminUser $user)
    {
        $user->delete();

        return redirect()->route('settings.index')
            ->with('success', 'User removed successfully.');
    }
}
