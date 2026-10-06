<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Désactive uniquement la vérification CSRF pour tous les tests.
     * Laravel 12 : le CSRF fait partie du groupe middleware 'web' via
     * Illuminate\Foundation\Http\Middleware\ValidateCsrfToken.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Désactive le CSRF uniquement — laisse auth, throttle et les autres actifs
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);
    }
}
