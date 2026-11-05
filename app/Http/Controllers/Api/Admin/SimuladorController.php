<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Simulacion\EstadoSimulador;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SimuladorController extends Controller
{
    public function __construct(
        private readonly EstadoSimulador $estadoSimulador,
    ) {}

    /**
     * GET /api/admin/simulador
     */
    public function show(): JsonResponse
    {
        return response()->json($this->estadoSimulador->aArray());
    }

    /**
     * POST /api/admin/simulador/abrir
     * Abre el simulador por una cantidad de horas (se cierra solo al vencer).
     */
    public function abrir(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'horas' => ['nullable', 'integer', 'min:1', 'max:8'],
        ]);

        $this->estadoSimulador->abrir((int) ($datos['horas'] ?? 2) * 60);

        return response()->json($this->estadoSimulador->aArray());
    }

    /**
     * POST /api/admin/simulador/cerrar
     */
    public function cerrar(): JsonResponse
    {
        $this->estadoSimulador->cerrar();

        return response()->json($this->estadoSimulador->aArray());
    }
}
