<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación de la sesión para la vista del alumno.
 * IMPORTANTE: Solo expone nombre, descripción y rol público del escenario
 * (nunca las necesidades ocultas, reglas de negocio ni instrucciones de la IA).
 *
 * @mixin \App\Models\Sesion
 */
class SesionAlumnoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $snapshot = $this->escenario_snapshot ?? [];

        return [
            'uuid' => $this->uuid,
            'estado' => $this->estado->value,
            'estado_etiqueta' => $this->estado->etiqueta(),
            'inicio' => $this->inicio?->toIso8601String(),
            'fin' => $this->fin?->toIso8601String(),
            'equipo' => [
                'id' => $this->equipo?->id,
                'nombre' => $this->equipo?->nombre,
                'codigo' => $this->equipo?->codigo,
            ],
            'escenario' => [
                'id' => $this->escenario_id,
                'nombre' => $snapshot['nombre'] ?? $this->escenario?->nombre,
                'descripcion' => $snapshot['descripcion'] ?? $this->escenario?->descripcion,
                'rol_cliente' => $snapshot['rol_cliente'] ?? $this->escenario?->rol_cliente,
            ],
            'limites' => [
                'max_caracteres_mensaje' => (int) config('gemini.max_caracteres_mensaje', 1000),
                'max_mensajes_sesion' => (int) config('gemini.max_mensajes_sesion', 80),
            ],
            'mensajes' => MensajeResource::collection($this->whenLoaded('mensajes')),
        ];
    }
}
