<?php

namespace Database\Factories;

use App\Enums\EstadoSesion;
use App\Models\Equipo;
use App\Models\Sesion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sesion>
 */
class SesionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'equipo_id' => Equipo::factory(),
            'escenario_id' => fn (array $attrs) => Equipo::find($attrs['equipo_id'])->escenario_id,
            'escenario_snapshot' => fn (array $attrs) => Equipo::find($attrs['equipo_id'])->escenario->aSnapshot(),
            'inicio' => now(),
            'estado' => EstadoSesion::Activa,
        ];
    }

    public function configure(): static
    {
        // El token_hash no es "fillable": se genera siempre con el método del modelo.
        return $this->afterMaking(fn (Sesion $sesion) => $sesion->generarToken());
    }

    public function finalizada(): static
    {
        return $this->state(fn () => ['estado' => EstadoSesion::Finalizada, 'fin' => now()]);
    }
}
