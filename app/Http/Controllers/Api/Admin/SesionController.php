<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\SesionAdminResource;
use App\Models\Sesion;
use App\Services\Simulacion\SimulacionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SesionController extends Controller
{
    public function __construct(
        private readonly SimulacionService $simulacionService,
    ) {}

    /**
     * GET /api/admin/sesiones
     * Lista de sesiones con Equipo | Escenario | Inicio | Estado | Mensajes.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Sesion::query()
            ->with(['equipo', 'escenario'])
            ->withCount('mensajes')
            ->orderByDesc('inicio')
            ->orderByDesc('id');

        if ($request->filled('equipo_id')) {
            $query->where('equipo_id', (int) $request->input('equipo_id'));
        }

        if ($request->filled('escenario_id')) {
            $query->where('escenario_id', (int) $request->input('escenario_id'));
        }

        if ($request->filled('estado')) {
            $query->where('estado', (string) $request->input('estado'));
        }

        return SesionAdminResource::collection($query->get());
    }

    /**
     * GET /api/admin/sesiones/{id}
     * Detalle completo de una sesión con toda su conversación cronológica.
     */
    public function show(int $id): SesionAdminResource
    {
        $sesion = Sesion::with(['equipo', 'escenario', 'mensajes'])
            ->withCount('mensajes')
            ->findOrFail($id);

        return new SesionAdminResource($sesion);
    }

    /**
     * POST /api/admin/sesiones/{id}/finalizar
     */
    public function finalizar(int $id): SesionAdminResource
    {
        $sesion = Sesion::findOrFail($id);
        $sesion = $this->simulacionService->finalizarSesion($sesion, 'Entrevista finalizada por el docente.');
        $sesion->load(['equipo', 'escenario', 'mensajes'])->loadCount('mensajes');

        return new SesionAdminResource($sesion);
    }

    /**
     * POST /api/admin/sesiones/{id}/reabrir
     * Vuelve a habilitar una entrevista finalizada para que el equipo la continúe.
     */
    public function reabrir(int $id): SesionAdminResource
    {
        $sesion = Sesion::findOrFail($id);
        $sesion = $this->simulacionService->reabrirSesion($sesion);
        $sesion->load(['equipo', 'escenario', 'mensajes'])->loadCount('mensajes');

        return new SesionAdminResource($sesion);
    }
}
