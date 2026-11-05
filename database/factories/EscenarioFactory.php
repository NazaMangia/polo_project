<?php

namespace Database\Factories;

use App\Enums\Dificultad;
use App\Enums\PoliticaRevelacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Escenario>
 */
class EscenarioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => 'Escenario '.fake()->unique()->words(2, true),
            'descripcion' => fake()->sentence(),
            'dificultad' => Dificultad::Basica,
            'activo' => true,
            'problema_principal' => fake()->sentence(),
            'contexto' => fake()->paragraph(),
            'rol_cliente' => 'Dueño del negocio',
            'personalidad' => 'Amable y directo.',
            'mensaje_inicial' => 'Hola, gracias por recibirme. Necesito ayuda con mi negocio.',
            'necesidades' => [
                ['id' => 'nec-1', 'texto' => fake()->sentence(), 'revelacion' => PoliticaRevelacion::SiPregunta->value],
            ],
            'actores' => [
                ['id' => 'act-1', 'texto' => 'Empleados de atención', 'revelacion' => PoliticaRevelacion::Espontaneo->value],
            ],
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
