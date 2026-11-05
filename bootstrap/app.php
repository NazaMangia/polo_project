<?php

use App\Http\Middleware\VerificarSimuladorAbierto;
use App\Http\Middleware\VerificarTokenSesion;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // La API es stateless: el docente se autentica con su token de Sanctum (Bearer) y los equipos
        // con X-Sesion-Token. No se usa statefulApi(): agregaría sesión y CSRF a /api, y una pestaña
        // abierta más de SESSION_LIFETIME minutos fallaría con "CSRF token mismatch" (419).
        $middleware->alias([
            'sesion.token' => VerificarTokenSesion::class,
            'simulador.abierto' => VerificarSimuladorAbierto::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Las rutas /api/* siempre responden JSON (incluso errores 404/500),
        // así el frontend Vue puede mostrar mensajes adecuados.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson()
        );
    })->create();
