<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'prenom'    => ['nullable', 'string', 'max:255'],
            'email'     => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'telephone' => ['nullable', 'string', 'max:20'],
            'role'      => ['nullable', 'in:DONATEUR,CLIENT,COLLECTEUR,ATELIER,RECYCLEUR,ADMIN'],
            'password'  => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'      => $request->name,
            'prenom'    => $request->prenom ?? '',
            'email'     => $request->email,
            'telephone' => $request->telephone,
            'role'      => $request->role ?? User::ROLE_DONATEUR,
            'password'  => Hash::make($request->password),
        ]);

        event(new Registered($user));
        Auth::login($user);

        $role = $user->role;

        return match($role) {
            'RECYCLEUR'  => redirect()->route('recyclage.dashboard'),
            'ADMIN'      => redirect()->route('admin.users.index'),
            'COLLECTEUR' => redirect()->route('logistique.tournees.index'),
            'ATELIER'    => redirect()->route('upcycling.dashboard'),
            default      => redirect()->route('dashboard'),
        };
    }
}
