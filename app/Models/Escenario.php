<?php

namespace App\Models;

use App\Enums\Dificultad;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Escenario extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Campos que contienen listas de ítems de conocimiento del cliente.
     * Cada ítem: ['id' => string, 'texto' => string, 'revelacion' => PoliticaRevelacion].
     * La clave es el nombre de columna y el valor, el título legible (usado en el prompt y en el panel).
     */
    public const LISTAS_CONOCIMIENTO = [
        'informacion_cliente' => 'Información general que conoce el cliente',
        'necesidades' => 'Necesidades',
        'actores' => 'Actores (personas o roles involucrados)',
        'procesos' => 'Procesos actuales',
        'datos' => 'Datos que se manejan',
        'reglas_negocio' => 'Reglas de negocio',
        'restricciones' => 'Restricciones',
    ];

    protected $fillable = [
        'nombre',
        'descripcion',
        'dificultad',
        'activo',
        'problema_principal',
        'contexto',
        'rol_cliente',
        'personalidad',
        'mensaje_inicial',
        'informacion_cliente',
        'necesidades',
        'actores',
        'procesos',
        'datos',
        'reglas_negocio',
        'restricciones',
        'informacion_reservada',
        'comportamiento_esperado',
        'instrucciones_ia',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'dificultad' => Dificultad::class,
            'informacion_cliente' => 'array',
            'necesidades' => 'array',
            'actores' => 'array',
            'procesos' => 'array',
            'datos' => 'array',
            'reglas_negocio' => 'array',
            'restricciones' => 'array',
        ];
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }

    public function sesiones(): HasMany
    {
        return $this->hasMany(Sesion::class);
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * Copia de todos los campos relevantes para la simulación.
     * Se guarda en la sesión al iniciarla (ver Sesion::escenario_snapshot).
     */
    public function aSnapshot(): array
    {
        return [
            'id' => $this->id,
            ...$this->only($this->fillable),
            'dificultad' => $this->dificultad?->value,
            'snapshot_tomado_en' => now()->toIso8601String(),
        ];
    }
}
