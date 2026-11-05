<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GuardarEscenarioRequest;
use App\Http\Resources\Admin\EscenarioAdminResource;
use App\Models\Escenario;
use App\Services\Simulacion\PromptBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EscenarioController extends Controller
{
    /**
     * GET /api/admin/escenarios
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Escenario::query()
            ->withCount(['equipos', 'sesiones'])
            ->orderBy('nombre');

        if ($request->filled('activo')) {
            $query->where('activo', filter_var($request->input('activo'), FILTER_VALIDATE_BOOLEAN));
        }

        return EscenarioAdminResource::collection($query->get());
    }

    /**
     * POST /api/admin/escenarios
     */
    public function store(GuardarEscenarioRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $datos['activo'] = $datos['activo'] ?? true;

        $escenario = Escenario::create($datos);
        $escenario->loadCount(['equipos', 'sesiones']);

        return (new EscenarioAdminResource($escenario))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/admin/escenarios/{escenario}
     */
    public function show(Escenario $escenario): EscenarioAdminResource
    {
        $escenario->loadCount(['equipos', 'sesiones']);

        return new EscenarioAdminResource($escenario);
    }

    /**
     * PUT /api/admin/escenarios/{escenario}
     */
    public function update(GuardarEscenarioRequest $request, Escenario $escenario): EscenarioAdminResource
    {
        $escenario->update($request->validated());
        $escenario->loadCount(['equipos', 'sesiones']);

        return new EscenarioAdminResource($escenario);
    }

    /**
     * DELETE /api/admin/escenarios/{escenario}
     */
    public function destroy(Escenario $escenario): JsonResponse
    {
        $escenario->delete();

        return response()->json([
            'message' => 'Escenario eliminado.',
        ]);
    }

    /**
     * POST /api/admin/escenarios/{escenario}/duplicar
     */
    public function duplicar(Escenario $escenario): JsonResponse
    {
        $copia = $escenario->replicate();
        $copia->nombre = $escenario->nombre.' (Copia)';
        $copia->activo = false;
        $copia->save();
        $copia->loadCount(['equipos', 'sesiones']);

        return (new EscenarioAdminResource($copia))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/admin/escenarios/{escenario}/prompt
     * Devuelve el prompt de sistema completo (capas 1 a 4) que recibe Gemini.
     */
    public function prompt(Escenario $escenario, PromptBuilder $promptBuilder): JsonResponse
    {
        return response()->json([
            'escenario_id' => $escenario->id,
            'nombre' => $escenario->nombre,
            'prompt' => $promptBuilder->construirInstruccionSistema($escenario),
        ]);
    }
}
