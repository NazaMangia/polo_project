<?php

namespace App\Http\Middleware;

use App\Services\Simulacion\EstadoSimulador;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea el ingreso de equipos y el envío de preguntas a la IA mientras el docente
 * no haya abierto el simulador desde el panel (o ya haya vencido el horario de apertura).
 */
class VerificarSimuladorAbierto
{
    public function __construct(
        private readonly EstadoSimulador $estadoSimulador,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->estadoSimulador->estaAbierto()) {
            return response()->json([
                'message' => 'El simulador está cerrado en este momento. El docente lo habilita durante la clase.',
                'simulador_cerrado' => true,
            ], 423);
        }

        return $next($request);
    }
}
