<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Webhook Stripe exclu de la vérification CSRF (Stripe signe ses requêtes avec STRIPE_WEBHOOK_SECRET)
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);

        // Alias : permissions des tokens Sanctum (ex. abilities:upcycling:ecrire) et rôle (ex. role:ATELIER,ADMIN)
        // 'role' reste EnsureRole (colonne users.role + pseudo-rôle ACHETEUR) ;
        // les middlewares Spatie sont exposés sous 'permission' et 'role_or_permission'.
        $middleware->alias([
            'abilities'          => CheckAbilities::class,
            'ability'            => CheckForAnyAbility::class,
            'role'               => \App\Http\Middleware\EnsureRole::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sous /api, toutes les erreurs sont rendues en JSON (401, 403, 404, 422, 429...)
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        // 404 de l'API : message neutre (ni modèle, ni chemin serveur, même en mode debug)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Ressource introuvable.'], 404);
            }
        });
    })->create();
