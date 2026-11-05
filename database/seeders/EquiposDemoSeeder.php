<?php

namespace Database\Seeders;

use App\Models\Equipo;
use App\Models\Escenario;
use Illuminate\Database\Seeder;

/**
 * Equipos de prueba para desarrollo.
 * Códigos fijos para que sea fácil probar desde la pantalla de inicio.
 */
class EquiposDemoSeeder extends Seeder
{
    public function run(): void
    {
        $clinica = Escenario::where('nombre', 'Sistema de turnos para una clínica')->first();

        $equipos = [
            ['nombre' => 'Equipo Omega', 'codigo' => 'OMEGA26', 'activo' => true, 'escenario_id' => $clinica?->id],
            ['nombre' => 'Equipo Orange', 'codigo' => 'ORANGE26', 'activo' => true, 'escenario_id' => $clinica?->id],
            ['nombre' => 'Equipo Sin Escenario', 'codigo' => 'SINESC26', 'activo' => true, 'escenario_id' => null],
            ['nombre' => 'Equipo Deshabilitado', 'codigo' => 'INACT26', 'activo' => false, 'escenario_id' => $clinica?->id],
        ];

        foreach ($equipos as $datos) {
            Equipo::updateOrCreate(['codigo' => $datos['codigo']], $datos);
        }
    }
}
