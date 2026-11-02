<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

/**
 * Deuxième étape de la connexion : code TOTP (application d'authentification)
 * ou code de récupération à usage unique.
 */
class DeuxFacteursConnexionController extends Controller
{
    /** Tentatives autorisées par minute pour un même compte en attente */
    private const TENTATIVES_MAX = 5;

    public function create(Request $request): View|RedirectResponse
    {
        if (! $this->utilisateurEnAttente($request)) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request, TwoFactorAuthenticationProvider $provider): RedirectResponse
    {
        $user = $this->utilisateurEnAttente($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $request->validate([
            'code'          => ['nullable', 'string', 'required_without:recovery_code'],
            'recovery_code' => ['nullable', 'string', 'required_without:code'],
        ], [
            'code.required_without'          => 'Saisissez le code à 6 chiffres ou un code de récupération.',
            'recovery_code.required_without' => 'Saisissez le code à 6 chiffres ou un code de récupération.',
        ]);

        $cle = 'deux-facteurs:'.$user->getKey().'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($cle, self::TENTATIVES_MAX)) {
            $secondes = RateLimiter::availableIn($cle);

            throw ValidationException::withMessages([
                'code' => "Trop de tentatives. Réessayez dans {$secondes} secondes.",
            ]);
        }

        if (! $this->codeValide($request, $user, $provider)) {
            RateLimiter::hit($cle, 60);

            $champ = $request->filled('recovery_code') ? 'recovery_code' : 'code';

            throw ValidationException::withMessages([
                $champ => $champ === 'code'
                    ? 'Le code de vérification est incorrect ou expiré.'
                    : 'Ce code de récupération est invalide ou a déjà été utilisé.',
            ]);
        }

        RateLimiter::clear($cle);

        Auth::guard('web')->login($user, (bool) $request->session()->pull('deux_facteurs.remember', false));
        $request->session()->forget('deux_facteurs.id');
        $request->session()->regenerate();

        return redirect()->intended(route($user->routeAccueil(), absolute: false));
    }

    private function utilisateurEnAttente(Request $request): ?User
    {
        $id = $request->session()->get('deux_facteurs.id');

        $user = $id ? User::find($id) : null;

        return $user?->hasEnabledTwoFactorAuthentication() ? $user : null;
    }

    private function codeValide(Request $request, User $user, TwoFactorAuthenticationProvider $provider): bool
    {
        if ($request->filled('code')) {
            $code = preg_replace('/\s+/', '', (string) $request->input('code'));

            return $provider->verify(Fortify::currentEncrypter()->decrypt($user->two_factor_secret), $code);
        }

        // Code de récupération : accepté une seule fois, puis remplacé par un nouveau
        $saisi = trim((string) $request->input('recovery_code'));
        $code = collect($user->recoveryCodes())->first(fn ($valide) => hash_equals($valide, $saisi));

        if (! $code) {
            return false;
        }

        $user->replaceRecoveryCode($code);

        return true;
    }
}
