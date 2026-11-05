<?php

namespace App\Models;

use App\Enums\RolMensaje;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mensaje extends Model
{
    use HasFactory;

    protected $fillable = [
        'sesion_id',
        'rol',
        'contenido',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'rol' => RolMensaje::class,
            'metadata' => 'array',
        ];
    }

    public function sesion(): BelongsTo
    {
        return $this->belongsTo(Sesion::class);
    }
}
