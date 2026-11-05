<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Escenario
 */
class EscenarioAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'dificultad' => $this->dificultad?->value,
            'dificultad_etiqueta' => $this->dificultad?->etiqueta(),
            'activo' => $this->activo,
            'problema_principal' => $this->problema_principal,
            'contexto' => $this->contexto,
            'rol_cliente' => $this->rol_cliente,
            'personalidad' => $this->personalidad,
            'mensaje_inicial' => $this->mensaje_inicial,
            'informacion_cliente' => $this->informacion_cliente ?? [],
            'necesidades' => $this->necesidades ?? [],
            'actores' => $this->actores ?? [],
            'procesos' => $this->procesos ?? [],
            'datos' => $this->datos ?? [],
            'reglas_negocio' => $this->reglas_negocio ?? [],
            'restricciones' => $this->restricciones ?? [],
            'informacion_reservada' => $this->informacion_reservada,
            'comportamiento_esperado' => $this->comportamiento_esperado,
            'instrucciones_ia' => $this->instrucciones_ia,
            'equipos_count' => $this->whenCounted('equipos'),
            'sesiones_count' => $this->whenCounted('sesiones'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
