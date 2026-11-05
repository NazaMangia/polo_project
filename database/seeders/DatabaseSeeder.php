<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Datos iniciales de la aplicación.
     *
     * En entorno de desarrollo local crea también un docente inicial si no hay ninguno
     * (`docente@escuela.edu.ar` / `docente1234`). En producción se usa `php artisan docente:crear`.
     */
    public function run(): void
    {
        $this->call(EscenarioClinicaSeeder::class);

        if (! app()->isProduction()) {
            $this->call(EquiposDemoSeeder::class);

            if (User::count() === 0) {
                User::create([
                    'name' => 'Docente Gestión de Proyectos',
                    'email' => 'docente@escuela.edu.ar',
                    'password' => Hash::make('docente1234'),
                ]);
            }
        }
    }
}
