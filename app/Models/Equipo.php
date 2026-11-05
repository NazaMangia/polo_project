<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'codigo',
        'activo',
        'escenario_id',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * El código se normaliza siempre (sin espacios, en mayúsculas),
     * así "omega26", " Omega26 " y "OMEGA26" son el mismo código.
     */
    protected function codigo(): Attribute
    {
        return Attribute::make(
            set: fn (?string $valor) => $valor === null ? null : self::normalizarCodigo($valor),
        );
    }

    public static function normalizarCodigo(string $codigo): string
    {
        return mb_strtoupper(preg_replace('/\s+/', '', $codigo));
    }

    /**
     * Busca un equipo por el código que ingresa el alumno (sin importar mayúsculas/espacios).
     */
    public static function buscarPorCodigo(string $codigo): ?self
    {
        return static::where('codigo', self::normalizarCodigo($codigo))->first();
    }

    public function escenario(): BelongsTo
    {
        return $this->belongsTo(Escenario::class);
    }

    public function sesiones(): HasMany
    {
        return $this->hasMany(Sesion::class);
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}
