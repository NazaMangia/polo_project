<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Sesion
 */
class SesionAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'estado' => $this->estado->value,
            'estado_etiqueta' => $this->estado->etiqueta(),
            'inicio' => $this->inicio?->toIso8601String(),
            'fin' => $this->fin?->toIso8601String(),
            'mensajes_count' => $this->whenCounted('mensajes'),
            'equipo' => $this->whenLoaded('equipo', fn () => $this->equipo ? [
                'id' => $this->equipo->id,
                'nombre' => $this->equipo->nombre,
                'codigo' => $this->equipo->codigo,
                'eliminado' => $this->equipo->trashed(),
            ] : null),
            'escenario' => [
                'id' => $this->escenario_id,
                'nombre' => $this->escenario_snapshot['nombre'] ?? $this->escenario?->nombre,
                'rol_cliente' => $this->escenario_snapshot['rol_cliente'] ?? $this->escenario?->rol_cliente,
                'dificultad' => $this->escenario_snapshot['dificultad'] ?? $this->escenario?->dificultad?->value,
            ],
            'mensajes' => $this->whenLoaded('mensajes', fn () => $this->mensajes->map(fn ($m) => [
                'id' => $m->id,
                'rol' => $m->rol->value,
                'rol_etiqueta' => $m->rol->etiqueta(),
                'contenido' => $m->contenido,
                'metadata' => $m->metadata,
                'created_at' => $m->created_at?->toIso8601String(),
            ])),
        ];
    }
}
