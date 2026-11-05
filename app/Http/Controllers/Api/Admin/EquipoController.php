<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\EstadoSesion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GuardarEquipoRequest;
use App\Http\Resources\Admin\EquipoAdminResource;
use App\Models\Equipo;
use App\Services\Equipos\GeneradorCodigoEquipo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EquipoController extends Controller
{
    public function __construct(
        private readonly GeneradorCodigoEquipo $generadorCodigo,
    ) {}

    /**
     * GET /api/admin/equipos
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Equipo::query()
            ->with('escenario')
            ->withCount([
                'sesiones',
                'sesiones as sesiones_activas_count' => fn ($q) => $q->where('estado', EstadoSesion::Activa),
            ])
            ->orderBy('nombre');

        if ($request->filled('buscar')) {
            $termino = trim((string) $request->input('buscar'));
            $query->where(function ($q) use ($termino) {
                $q->where('nombre', 'like', "%{$termino}%")
                    ->orWhere('codigo', 'like', "%{$termino}%");
            });
        }

        if ($request->filled('activo')) {
            $query->where('activo', filter_var($request->input('activo'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('escenario_id')) {
            $query->where('escenario_id', (int) $request->input('escenario_id'));
        }

        return EquipoAdminResource::collection($query->get());
    }

    /**
     * POST /api/admin/equipos
     */
    public function store(GuardarEquipoRequest $request): JsonResponse
    {
        $datos = $request->validated();

        if (empty($datos['codigo'])) {
            $datos['codigo'] = $this->generadorCodigo->generar($datos['nombre']);
        }

        $datos['activo'] = $datos['activo'] ?? true;

        $equipo = Equipo::create($datos);
        $equipo->load('escenario')->loadCount('sesiones');

        return (new EquipoAdminResource($equipo))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/admin/equipos/{equipo}
     */
    public function show(Equipo $equipo): EquipoAdminResource
    {
        $equipo->load('escenario')->loadCount('sesiones');

        return new EquipoAdminResource($equipo);
    }

    /**
     * PUT /api/admin/equipos/{equipo}
     */
    public function update(GuardarEquipoRequest $request, Equipo $equipo): EquipoAdminResource
    {
        $datos = $request->validated();

        if (array_key_exists('codigo', $datos) && empty($datos['codigo'])) {
            unset($datos['codigo']);
        }

        $equipo->update($datos);
        $equipo->load('escenario')->loadCount('sesiones');

        return new EquipoAdminResource($equipo);
    }

    /**
     * DELETE /api/admin/equipos/{equipo}
     */
    public function destroy(Equipo $equipo): JsonResponse
    {
        $equipo->delete();

        return response()->json([
            'message' => 'Equipo eliminado.',
        ]);
    }

    /**
     * POST /api/admin/equipos/{equipo}/regenerar-codigo
     */
    public function regenerarCodigo(Equipo $equipo): EquipoAdminResource
    {
        $equipo->codigo = $this->generadorCodigo->generar($equipo->nombre);
        $equipo->save();

        $equipo->load('escenario')->loadCount('sesiones');

        return new EquipoAdminResource($equipo);
    }
}
