<?php

use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Request $request, Exception $exception) {
            if ($request->is('api/*')) {
                if ($exception instanceof MissingAbilityException) {
                    return response()->json([
                        'code' => 403,
                        'message' => 'Forbidden - missing ability',
                        'error' => $exception->getMessage(),
                    ], 403);
                }
        
                return response()->json([
                    'code' => 500,
                    'message' => 'Server Error',
                    'error' => $exception->getMessage(),
                ], 500);
            }
        });
    })->create();
