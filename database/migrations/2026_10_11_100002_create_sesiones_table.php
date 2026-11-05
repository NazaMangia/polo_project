<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesiones', function (Blueprint $table) {
            $table->id();

            // Identificador público (va en la URL). No es adivinable como un ID numérico.
            $table->uuid('uuid')->unique();

            // restrictOnDelete: no se puede borrar físicamente un equipo/escenario con sesiones
            // (en la app se usan borrados lógicos, así el historial se conserva).
            $table->foreignId('equipo_id')->constrained('equipos')->restrictOnDelete();
            $table->foreignId('escenario_id')->constrained('escenarios')->restrictOnDelete();

            // Hash SHA-256 del token secreto que tiene el navegador del equipo.
            $table->string('token_hash', 64);

            // Copia del escenario al momento de iniciar: si el docente lo edita,
            // la entrevista en curso sigue siendo coherente y la revisión es fiel.
            $table->json('escenario_snapshot');

            $table->dateTime('inicio');
            $table->dateTime('fin')->nullable();
            $table->string('estado', 20)->default('activa'); // App\Enums\EstadoSesion

            $table->timestamps();

            $table->index(['equipo_id', 'escenario_id', 'estado']);
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones');
    }
};
