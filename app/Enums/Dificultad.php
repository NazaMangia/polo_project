<?php

namespace App\Enums;

enum Dificultad: string
{
    case Basica = 'basica';
    case Intermedia = 'intermedia';
    case Avanzada = 'avanzada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Basica => 'Básica',
            self::Intermedia => 'Intermedia',
            self::Avanzada => 'Avanzada',
        };
    }

    /**
     * Cómo se comporta el cliente según la dificultad.
     * Se incorpora al prompt que recibe la IA (fase 2).
     */
    public function comportamiento(): string
    {
        return match ($this) {
            self::Basica => 'Sos un cliente colaborador y claro. Respondés de forma ordenada y bastante completa a lo que te preguntan, aunque sin adelantar información que no te pidieron.',
            self::Intermedia => 'Sos un cliente algo disperso. A veces respondés de forma vaga o te vas por las ramas, y el entrevistador tiene que repreguntar para obtener precisión. Mezclás necesidades con quejas.',
            self::Avanzada => 'Sos un cliente impreciso y ocupado. Das respuestas cortas o ambiguas, das cosas por sabidas, usás términos propios de tu negocio sin explicarlos y podés tener pequeñas contradicciones que el entrevistador debería detectar y aclarar. Muchos requisitos son implícitos.',
        };
    }
}
