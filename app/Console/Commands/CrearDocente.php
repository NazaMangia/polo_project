<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Crea (o actualiza la contraseña de) un usuario docente.
 * No existe registro público: esta es la única forma de dar de alta docentes.
 *
 * Uso: php artisan docente:crear
 */
class CrearDocente extends Command
{
    protected $signature = 'docente:crear
                            {--nombre= : Nombre del docente}
                            {--email= : Email con el que va a iniciar sesión}';

    protected $description = 'Crea un usuario docente para el panel de administración (o cambia su contraseña si ya existe)';

    public function handle(): int
    {
        $nombre = $this->option('nombre') ?: text(label: 'Nombre del docente', required: true);

        $email = $this->option('email') ?: text(
            label: 'Email',
            required: true,
            validate: fn (string $v) => filter_var($v, FILTER_VALIDATE_EMAIL) ? null : 'Email inválido.',
        );

        $clave = password(
            label: 'Contraseña (mínimo 8 caracteres)',
            required: true,
            validate: fn (string $v) => mb_strlen($v) >= 8 ? null : 'Debe tener al menos 8 caracteres.',
        );

        $confirmacion = password(label: 'Repetí la contraseña', required: true);

        $validador = Validator::make(
            ['nombre' => $nombre, 'email' => $email, 'clave' => $clave, 'clave_confirmation' => $confirmacion],
            ['nombre' => 'required|string|max:255', 'email' => 'required|email|max:255', 'clave' => 'required|string|min:8|confirmed'],
            ['clave.confirmed' => 'Las contraseñas no coinciden.'],
        );

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $usuario = User::updateOrCreate(
            ['email' => mb_strtolower($email)],
            ['name' => $nombre, 'password' => Hash::make($clave)],
        );

        $this->info($usuario->wasRecentlyCreated
            ? "Docente creado: {$usuario->email}"
            : "Docente existente actualizado (nueva contraseña): {$usuario->email}");

        return self::SUCCESS;
    }
}
