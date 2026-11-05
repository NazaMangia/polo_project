<?php

namespace App\Models;

use App\Enums\EstadoSesion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sesion extends Model
{
    use HasFactory, HasUuids;

    /** Laravel pluralizaría "sesions": se indica el nombre real de la tabla. */
    protected $table = 'sesiones';

    protected $fillable = [
        'equipo_id',
        'escenario_id',
        'escenario_snapshot',
        'inicio',
        'fin',
        'estado',
    ];

    /** Nunca se exponen en respuestas JSON. */
    protected $hidden = [
        'token_hash',
        'escenario_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'escenario_snapshot' => 'array',
            'inicio' => 'datetime',
            'fin' => 'datetime',
            'estado' => EstadoSesion::class,
        ];
    }

    /**
     * HasUuids genera automáticamente la columna "uuid" (no la clave primaria, que sigue siendo numérica).
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * Las rutas usan el UUID: /api/sesiones/{uuid}. Así no se pueden recorrer sesiones cambiando un número.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ---------------------------------------------------------------------
    // Token secreto de acceso
    // ---------------------------------------------------------------------

    /**
     * Devuelve el token de acceso de la sesión y guarda solo su hash.
     *
     * El token se deriva del uuid firmado con la APP_KEY (HMAC): es siempre el mismo para
     * la misma sesión, así cada integrante que entra con el código del equipo recibe un token
     * válido sin invalidar el de sus compañeros. Sin la APP_KEY no se puede calcular.
     */
    public function generarToken(): string
    {
        $this->uuid ??= $this->newUniqueId();

        $token = hash_hmac('sha256', 'sesion-alumno:'.$this->uuid, (string) config('app.key'));
        $this->token_hash = hash('sha256', $token);

        return $token;
    }

    public function verificarToken(?string $token): bool
    {
        if (! $token || ! $this->token_hash) {
            return false;
        }

        return hash_equals($this->token_hash, hash('sha256', $token));
    }

    // ---------------------------------------------------------------------
    // Estado
    // ---------------------------------------------------------------------

    public function estaActiva(): bool
    {
        return $this->estado === EstadoSesion::Activa;
    }

    public function finalizar(): void
    {
        $this->forceFill([
            'estado' => EstadoSesion::Finalizada,
            'fin' => now(),
        ])->save();
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('estado', EstadoSesion::Activa);
    }

    // ---------------------------------------------------------------------
    // Relaciones
    // ---------------------------------------------------------------------

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class)->withTrashed();
    }

    public function escenario(): BelongsTo
    {
        return $this->belongsTo(Escenario::class)->withTrashed();
    }

    public function mensajes(): HasMany
    {
        return $this->hasMany(Mensaje::class)->orderBy('id');
    }
}
