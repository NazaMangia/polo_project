<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Mensaje
 */
class MensajeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rol' => $this->rol->value,
            'rol_etiqueta' => $this->rol->etiqueta(),
            'contenido' => $this->contenido,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
