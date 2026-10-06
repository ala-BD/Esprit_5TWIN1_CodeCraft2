<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's role.
     */
    public function updateRole(Request $request): RedirectResponse
    {
        // ADMIN role cannot be self-assigned
        $allowedRoles = [
            \App\Models\User::ROLE_DONATEUR,
            \App\Models\User::ROLE_CLIENT,
            \App\Models\User::ROLE_COLLECTEUR,
            \App\Models\User::ROLE_ATELIER,
            \App\Models\User::ROLE_RECYCLEUR,
        ];

        $request->validate([
            'role' => ['required', 'string', \Illuminate\Validation\Rule::in($allowedRoles)],
        ]);

        $user = $request->user();
        $user->role = $request->role;
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'role-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
