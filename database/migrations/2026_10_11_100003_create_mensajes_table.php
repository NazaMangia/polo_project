<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_id')->constrained('sesiones')->cascadeOnDelete();
            $table->string('rol', 20); // App\Enums\RolMensaje
            $table->text('contenido');

            // Datos técnicos de la respuesta de la IA: modelo, tokens, latencia, etc.
            // Útil para controlar costos y para la evaluación automática futura.
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['sesion_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
};
