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
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $role = $request->user()->role;

        $dashboardRoutes = [
            \App\Models\User::ROLE_ADMIN      => 'dashboard.admin',
            \App\Models\User::ROLE_ATELIER    => 'dashboard.atelier',
            \App\Models\User::ROLE_CLIENT     => 'dashboard.client',
            \App\Models\User::ROLE_DONATEUR   => 'dashboard.donateur',
            \App\Models\User::ROLE_COLLECTEUR => 'dashboard.collecteur',
            \App\Models\User::ROLE_RECYCLEUR  => 'dashboard.recycleur',
        ];

        $targetRoute = $dashboardRoutes[$role] ?? 'dashboard';

        return redirect()->intended(route($targetRoute, absolute: false));
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
