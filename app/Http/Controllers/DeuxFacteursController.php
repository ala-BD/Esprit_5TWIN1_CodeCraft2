<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Fortify;

/**
 * Gestion de l'authentification à deux facteurs depuis le profil
 * (actions Fortify ; mot de passe confirmé via le middleware password.confirm).
 */
class DeuxFacteursController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('profile.deux-facteurs', [
            'user'     => $user,
            'enCours'  => $user->two_factor_secret && ! $user->two_factor_confirmed_at,
            'active'   => $user->hasEnabledTwoFactorAuthentication(),
            'cle'      => $user->two_factor_secret ? Fortify::currentEncrypter()->decrypt($user->two_factor_secret) : null,
            'codes'    => $user->two_factor_recovery_codes ? $user->recoveryCodes() : [],
        ]);
    }

    public function store(Request $request, EnableTwoFactorAuthentication $activer): RedirectResponse
    {
        $activer($request->user());

        return redirect()->route('deux-facteurs.show')->with('status', 'deux-facteurs-active');
    }

    public function confirmer(Request $request, ConfirmTwoFactorAuthentication $confirmer): RedirectResponse
    {
        $request->validate(
            ['code' => ['required', 'digits:6']],
            ['code.required' => 'Saisissez le code à 6 chiffres.', 'code.digits' => 'Le code doit contenir 6 chiffres.'],
        );

        $confirmer($request->user(), $request->input('code'));

        return redirect()->route('deux-facteurs.show')->with('status', 'deux-facteurs-confirme');
    }

    public function regenererCodes(Request $request, GenerateNewRecoveryCodes $generer): RedirectResponse
    {
        $generer($request->user());

        return redirect()->route('deux-facteurs.show')->with('status', 'codes-regeneres');
    }

    public function destroy(Request $request, DisableTwoFactorAuthentication $desactiver): RedirectResponse
    {
        $desactiver($request->user());

        return redirect()->route('deux-facteurs.show')->with('status', 'deux-facteurs-desactive');
    }
}
