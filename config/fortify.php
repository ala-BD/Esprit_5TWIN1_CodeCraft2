<?php

use Laravel\Fortify\Features;

/*
|--------------------------------------------------------------------------
| Fortify — utilisé uniquement pour l'authentification à deux facteurs
|--------------------------------------------------------------------------
| Connexion, inscription, mot de passe oublié et vérification d'e-mail
| restent gérés par Breeze. Les routes Fortify sont désactivées
| (Fortify::ignoreRoutes() dans AppServiceProvider) et ses vues aussi.
*/

return [

    'guard' => 'web',

    'passwords' => 'users',

    'username' => 'email',

    'email' => 'email',

    'lowercase_usernames' => true,

    'home' => '/dashboard',

    'prefix' => '',

    'domain' => null,

    'middleware' => ['web'],

    'limiters' => [
        'login'      => 'login',
        'two-factor' => 'two-factor',
    ],

    'views' => false,

    'features' => [
        // confirm : la 2FA n'est active qu'après la saisie d'un premier code valide
        Features::twoFactorAuthentication([
            'confirm'         => true,
            'confirmPassword' => true,
        ]),
    ],

];
