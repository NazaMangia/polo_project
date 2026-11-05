<?php

namespace App\Enums;

/**
 * Cuándo puede el cliente virtual revelar un dato del escenario.
 */
enum PoliticaRevelacion: string
{
    /** Lo puede mencionar por iniciativa propia. */
    case Espontaneo = 'espontaneo';

    /** Solo si el alumno pregunta por ese tema. */
    case SiPregunta = 'si_pregunta';

    /** Solo ante una pregunta puntual y bien formulada. */
    case MuyEspecifico = 'muy_especifico';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Espontaneo => 'Lo cuenta solo',
            self::SiPregunta => 'Si le preguntan',
            self::MuyEspecifico => 'Solo con pregunta precisa',
        };
    }
}
