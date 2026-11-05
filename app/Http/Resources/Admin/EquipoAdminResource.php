<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Equipo
 */
class EquipoAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'codigo' => $this->codigo,
            'activo' => $this->activo,
            'escenario_id' => $this->escenario_id,
            'escenario' => $this->whenLoaded('escenario', fn () => $this->escenario ? [
                'id' => $this->escenario->id,
                'nombre' => $this->escenario->nombre,
                'activo' => $this->escenario->activo,
                'dificultad' => $this->escenario->dificultad?->value,
                'dificultad_etiqueta' => $this->escenario->dificultad?->etiqueta(),
            ] : null),
            'sesiones_count' => $this->whenCounted('sesiones'),
            'sesiones_activas_count' => $this->when(
                isset($this->sesiones_activas_count),
                $this->sesiones_activas_count,
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
