<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escenarios', function (Blueprint $table) {
            $table->id();

            // Datos generales (visibles para el alumno: nombre y descripción)
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->string('dificultad', 20)->default('basica'); // App\Enums\Dificultad
            $table->boolean('activo')->default(true);

            // Contexto narrativo del cliente (texto libre)
            $table->text('problema_principal');
            $table->text('contexto')->nullable();
            $table->text('rol_cliente');                 // ej. "Dueña de la clínica"
            $table->text('personalidad')->nullable();
            $table->text('mensaje_inicial');             // saludo con el que abre la entrevista

            // Conocimiento del cliente: listas de ítems con política de revelación.
            // Formato: [{"id": "nec-1", "texto": "...", "revelacion": "si_pregunta"}]
            $table->json('informacion_cliente')->nullable();
            $table->json('necesidades')->nullable();
            $table->json('actores')->nullable();
            $table->json('procesos')->nullable();
            $table->json('datos')->nullable();
            $table->json('reglas_negocio')->nullable();
            $table->json('restricciones')->nullable();

            // Indicaciones adicionales para la IA (texto libre)
            $table->text('informacion_reservada')->nullable(); // "secretos" que no encajan en las listas
            $table->text('comportamiento_esperado')->nullable();
            $table->text('instrucciones_ia')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escenarios');
    }
};
