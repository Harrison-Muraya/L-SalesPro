<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api', // This sets /api prefix for all routes in api.php
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register custom middleware aliases
        $middleware->alias([
            'check.credit' => \App\Http\Middleware\CheckCreditLimit::class,
            'log.activity' => \App\Http\Middleware\LogApiActivity::class,
        ]);

        // Handle unauthenticated requests (API returns JSON, not redirect)
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                    'errors' => ['auth' => ['Authentication required to access this resource.']]
                ], 401));
            }
            
            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle 404 errors for API
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                    'errors' => ['route' => ['The requested endpoint does not exist.']]
                ], 404);
            }
        });
    })->create();