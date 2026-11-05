<?php

namespace App\Services\Equipos;

use App\Models\Equipo;
use Illuminate\Support\Str;

/**
 * Genera códigos de equipo fáciles de dictar y copiar, por ejemplo "OMEGA-7K4".
 *
 * - Prefijo: derivado del nombre del equipo (sin acentos, sin la palabra "equipo").
 * - Sufijo: caracteres aleatorios SIN símbolos ambiguos (no usa 0/O, 1/I/L).
 */
class GeneradorCodigoEquipo
{
    private const ALFABETO = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const LARGO_PREFIJO = 6;

    private const LARGO_SUFIJO = 3;

    public function generar(string $nombreEquipo): string
    {
        $prefijo = $this->prefijoDesdeNombre($nombreEquipo);

        do {
            $codigo = $prefijo.'-'.$this->sufijoAleatorio();
        } while ($this->existe($codigo));

        return $codigo;
    }

    private function prefijoDesdeNombre(string $nombre): string
    {
        $limpio = Str::of($nombre)
            ->ascii()
            ->upper()
            ->replaceMatches('/\bEQUIPO\b/', '')
            ->replaceMatches('/[^A-Z0-9]/', '')
            ->substr(0, self::LARGO_PREFIJO)
            ->toString();

        return $limpio !== '' ? $limpio : 'EQUIPO';
    }

    private function sufijoAleatorio(): string
    {
        $sufijo = '';
        $max = strlen(self::ALFABETO) - 1;

        for ($i = 0; $i < self::LARGO_SUFIJO; $i++) {
            $sufijo .= self::ALFABETO[random_int(0, $max)];
        }

        return $sufijo;
    }

    /** Incluye equipos borrados lógicamente, porque el índice único de la tabla también los incluye. */
    private function existe(string $codigo): bool
    {
        return Equipo::withTrashed()->where('codigo', $codigo)->exists();
    }
}
