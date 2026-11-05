<?php

namespace Database\Factories;

use App\Models\Escenario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Equipo>
 */
class EquipoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => 'Equipo '.fake()->unique()->firstName(),
            'codigo' => strtoupper(fake()->unique()->bothify('TEST-###??')),
            'activo' => true,
            'escenario_id' => Escenario::factory(),
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }

    public function sinEscenario(): static
    {
        return $this->state(fn () => ['escenario_id' => null]);
    }
}
