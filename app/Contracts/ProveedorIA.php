<?php

namespace App\Contracts;

use App\Services\Gemini\RespuestaIA;

interface ProveedorIA
{
    /**
     * Genera la siguiente respuesta del cliente virtual.
     *
     * @param  string  $instruccionSistema  Prompt completo de las capas 1 a 4.
     * @param  array<int, array{rol: string, contenido: string}>  $historial  Mensajes previos y pregunta actual.
     *
     * @throws \App\Exceptions\ProveedorIAException
     */
    public function responder(string $instruccionSistema, array $historial): RespuestaIA;
}
