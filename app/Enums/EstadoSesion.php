<?php

namespace App\Enums;

enum EstadoSesion: string
{
    case Activa = 'activa';
    case Finalizada = 'finalizada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Activa => 'Activa',
            self::Finalizada => 'Finalizada',
        };
    }
}
