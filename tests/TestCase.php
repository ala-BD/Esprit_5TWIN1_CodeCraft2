<?php

namespace Tests;

use Database\Seeders\RolesEtPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    /**
     * Avec RefreshDatabase : rôles et permissions Spatie créés après chaque migration,
     * pour que les utilisateurs de la fabrique aient les mêmes droits qu'en production.
     */
    protected bool $seed = true;

    protected string $seeder = RolesEtPermissionsSeeder::class;

    /**
     * Désactive uniquement la vérification CSRF pour tous les tests.
     * Laravel 12 : le CSRF fait partie du groupe middleware 'web' via
     * Illuminate\Foundation\Http\Middleware\ValidateCsrfToken.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Cache des permissions Spatie remis à zéro entre deux tests
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // Désactive le CSRF uniquement — laisse auth, throttle et les autres actifs
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);
    }
}
