<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumno\IniciarSesionRequest;
use App\Http\Resources\SesionAlumnoResource;
use App\Models\Sesion;
use App\Services\Simulacion\SimulacionService;
use Illuminate\Http\JsonResponse;

class SesionController extends Controller
{
    public function __construct(
        private readonly SimulacionService $simulacionService,
    ) {}

    /**
     * POST /api/sesiones
     * Valida el código del equipo e inicia una sesión nueva (o retoma la que tenga activa).
     */
    public function store(IniciarSesionRequest $request): JsonResponse
    {
        $resultado = $this->simulacionService->iniciarOReanudarPorCodigo(
            $request->validated('codigo'),
        );

        return response()->json([
            'token' => $resultado['token'],
            'reanudada' => $resultado['reanudada'],
            'sesion' => new SesionAlumnoResource($resultado['sesion']),
        ], $resultado['reanudada'] ? 200 : 201);
    }

    /**
     * GET /api/sesiones/{sesion}
     */
    public function show(Sesion $sesion): SesionAlumnoResource
    {
        $sesion->load(['equipo', 'escenario', 'mensajes']);

        return new SesionAlumnoResource($sesion);
    }

    /**
     * POST /api/sesiones/{sesion}/finalizar
     */
    public function finalizar(Sesion $sesion): SesionAlumnoResource
    {
        $sesion = $this->simulacionService->finalizarSesion($sesion);
        $sesion->load(['equipo', 'escenario', 'mensajes']);

        return new SesionAlumnoResource($sesion);
    }
}
