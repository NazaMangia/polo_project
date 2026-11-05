<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ProveedorIAException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Alumno\EnviarMensajeRequest;
use App\Http\Resources\MensajeResource;
use App\Models\Sesion;
use App\Services\Simulacion\SimulacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MensajeController extends Controller
{
    public function __construct(
        private readonly SimulacionService $simulacionService,
    ) {}

    /**
     * GET /api/sesiones/{sesion}/mensajes
     */
    public function index(Sesion $sesion): AnonymousResourceCollection
    {
        return MensajeResource::collection($sesion->mensajes()->get());
    }

    /**
     * POST /api/sesiones/{sesion}/mensajes
     */
    public function store(EnviarMensajeRequest $request, Sesion $sesion): JsonResponse
    {
        try {
            $resultado = $this->simulacionService->enviarMensajeAlumno(
                $sesion,
                $request->validated('contenido'),
            );
        } catch (ProveedorIAException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 503);
        }

        return response()->json([
            'mensaje_alumno' => new MensajeResource($resultado['mensaje_alumno']),
            'mensaje_cliente' => new MensajeResource($resultado['mensaje_cliente']),
        ], 201);
    }
}
