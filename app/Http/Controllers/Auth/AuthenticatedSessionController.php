<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     * Redirige vers le bon espace selon le rôle de l'utilisateur.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        // Chaque rôle arrive dans l'espace de son module ; CLIENT et DONATEUR sur le tableau de bord général.
        // intended() renvoie d'abord vers la page demandée avant la connexion, s'il y en a une.
        $routeParRole = [
            \App\Models\User::ROLE_RECYCLEUR  => 'recyclage.dashboard',
            \App\Models\User::ROLE_ADMIN      => 'admin.users.index',
            \App\Models\User::ROLE_COLLECTEUR => 'logistique.tournees.index',
            \App\Models\User::ROLE_ATELIER    => 'upcycling.dashboard',
        ];

        $routeCible = $routeParRole[$request->user()->role] ?? 'dashboard';

        return redirect()->intended(route($routeCible, absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
