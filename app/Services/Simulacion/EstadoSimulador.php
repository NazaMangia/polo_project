<?php

namespace App\Services\Simulacion;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Interruptor general del simulador que maneja el docente desde el panel.
 *
 * Se guarda "abierto hasta" (fecha y hora) en lugar de un sí/no: así, si el docente
 * se olvida de cerrarlo al terminar la clase, se cierra solo y no se siguen gastando
 * créditos de la IA. Por defecto (sin registro) el simulador está cerrado.
 */
class EstadoSimulador
{
    private const CLAVE = 'simulador_abierto_hasta';

    public function abiertoHasta(): ?Carbon
    {
        $valor = DB::table('ajustes')->where('clave', self::CLAVE)->value('valor');

        return $valor ? Carbon::parse($valor) : null;
    }

    public function estaAbierto(): bool
    {
        $hasta = $this->abiertoHasta();

        return $hasta !== null && $hasta->isFuture();
    }

    public function abrir(int $minutos): Carbon
    {
        $hasta = now()->addMinutes($minutos);
        $this->guardar($hasta->toIso8601String());

        return $hasta;
    }

    public function cerrar(): void
    {
        $this->guardar(null);
    }

    /**
     * @return array{abierto: bool, abierto_hasta: ?string}
     */
    public function aArray(): array
    {
        $abierto = $this->estaAbierto();

        return [
            'abierto' => $abierto,
            'abierto_hasta' => $abierto ? $this->abiertoHasta()->toIso8601String() : null,
        ];
    }

    private function guardar(?string $valor): void
    {
        DB::table('ajustes')->updateOrInsert(
            ['clave' => self::CLAVE],
            ['valor' => $valor, 'updated_at' => now(), 'created_at' => now()],
        );
    }
}
