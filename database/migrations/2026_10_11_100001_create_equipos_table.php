<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('codigo', 20)->unique(); // se guarda siempre en mayúsculas
            $table->boolean('activo')->default(true);

            // Escenario asignado por el docente. Sin escenario, el equipo no puede iniciar.
            $table->foreignId('escenario_id')->nullable()->constrained('escenarios')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
