<?php

use App\Http\Controllers\Api\Admin\AuthDocenteController;
use App\Http\Controllers\Api\Admin\EquipoController as AdminEquipoController;
use App\Http\Controllers\Api\Admin\EscenarioController as AdminEscenarioController;
use App\Http\Controllers\Api\Admin\SesionController as AdminSesionController;
use App\Http\Controllers\Api\Admin\SimuladorController as AdminSimuladorController;
use App\Http\Controllers\Api\MensajeController;
use App\Http\Controllers\Api\SesionController;
use App\Services\Simulacion\EstadoSimulador;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de la API
|--------------------------------------------------------------------------
*/

Route::get('/estado', fn () => response()->json([
    'app' => config('app.name'),
    'laravel' => app()->version(),
    'hora_servidor' => now()->toIso8601String(),
    'simulador' => app(EstadoSimulador::class)->aArray(),
]));

// --- Flujo del Alumno ---
// Ingresar y preguntarle a la IA solo funciona mientras el docente tenga el simulador abierto.
// Ver la conversación ya existente y finalizarla se permite siempre.
Route::post('/sesiones', [SesionController::class, 'store'])
    ->middleware(['simulador.abierto', 'throttle:iniciar-sesion']);

Route::middleware('sesion.token')->group(function () {
    Route::get('/sesiones/{sesion}', [SesionController::class, 'show']);
    Route::get('/sesiones/{sesion}/mensajes', [MensajeController::class, 'index']);
    Route::post('/sesiones/{sesion}/mensajes', [MensajeController::class, 'store'])
        ->middleware(['simulador.abierto', 'throttle:mensajes-ia']);
    Route::post('/sesiones/{sesion}/finalizar', [SesionController::class, 'finalizar']);
});

// --- Autenticación del Docente ---
Route::post('/docente/login', [AuthDocenteController::class, 'login'])
    ->middleware('throttle:login-docente');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/docente/yo', [AuthDocenteController::class, 'yo']);
    Route::post('/docente/logout', [AuthDocenteController::class, 'logout']);

    // --- Panel Administrativo del Docente ---
    Route::prefix('admin')->group(function () {
        // Interruptor general del simulador (abrir al empezar la clase, cerrar al terminar)
        Route::get('simulador', [AdminSimuladorController::class, 'show']);
        Route::post('simulador/abrir', [AdminSimuladorController::class, 'abrir']);
        Route::post('simulador/cerrar', [AdminSimuladorController::class, 'cerrar']);

        // Equipos
        Route::apiResource('equipos', AdminEquipoController::class);
        Route::post('equipos/{equipo}/regenerar-codigo', [AdminEquipoController::class, 'regenerarCodigo']);

        // Escenarios
        Route::apiResource('escenarios', AdminEscenarioController::class);
        Route::post('escenarios/{escenario}/duplicar', [AdminEscenarioController::class, 'duplicar']);
        Route::get('escenarios/{escenario}/prompt', [AdminEscenarioController::class, 'prompt']);

        // Sesiones y conversaciones
        Route::get('sesiones', [AdminSesionController::class, 'index']);
        Route::get('sesiones/{id}', [AdminSesionController::class, 'show'])->whereNumber('id');
        Route::post('sesiones/{id}/finalizar', [AdminSesionController::class, 'finalizar'])->whereNumber('id');
    });
});
