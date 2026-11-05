<?php

namespace App\Enums;

enum RolMensaje: string
{
    /** Pregunta escrita por el equipo de alumnos. */
    case Alumno = 'alumno';

    /** Respuesta del cliente virtual (generada por la IA o el mensaje inicial del escenario). */
    case Cliente = 'cliente';

    /** Avisos internos (ej. "La entrevista fue finalizada"). No se envían a la IA. */
    case Sistema = 'sistema';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Alumno => 'Alumno',
            self::Cliente => 'Cliente',
            self::Sistema => 'Sistema',
        };
    }
}
