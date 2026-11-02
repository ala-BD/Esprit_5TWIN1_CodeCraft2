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

        $user = $request->user();

        // 2FA confirmée : la connexion n'est pas terminée tant que le code n'est pas saisi
        if ($user->hasEnabledTwoFactorAuthentication()) {
            Auth::guard('web')->logout();

            $request->session()->regenerate();
            $request->session()->put([
                'deux_facteurs.id'       => $user->getKey(),
                'deux_facteurs.remember' => $request->boolean('remember'),
            ]);

            return redirect()->route('deux-facteurs.connexion');
        }

        $request->session()->regenerate();

        // Chaque rôle arrive dans l'espace de son module (User::routeAccueil()).
        // intended() renvoie d'abord vers la page demandée avant la connexion, s'il y en a une.
        return redirect()->intended(route($user->routeAccueil(), absolute: false));
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
