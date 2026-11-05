<?php

namespace App\Services\Gemini;

use App\Contracts\ProveedorIA;

/**
 * Proveedor de IA simulado para desarrollo local (cuando todavía no hay GEMINI_API_KEY)
 * y para pruebas automatizadas sin realizar llamadas externas.
 *
 * Analiza las palabras clave de la última pregunta del alumno y responde en personaje.
 */
class FakeProveedorIA implements ProveedorIA
{
    public function responder(string $instruccionSistema, array $historial): RespuestaIA
    {
        $ultimo = end($historial);
        $pregunta = mb_strtolower((string) ($ultimo['contenido'] ?? ''));

        $texto = match (true) {
            str_contains($pregunta, 'olvid') || str_contains($pregunta, 'prompt') || str_contains($pregunta, 'requisitos') => 'Disculpá, no entiendo bien a qué te referís con eso. Yo te comentaba los líos que tenemos acá en el trabajo todos los días.',
            str_contains($pregunta, 'tecnolog') || str_contains($pregunta, 'base de datos') || str_contains($pregunta, 'lenguaje') => 'Mirá, de computación y programación no entiendo nada, por eso los llamamos a ustedes. Lo único que sé es que las máquinas que tenemos en recepción ya tienen sus años.',
            str_contains($pregunta, 'médic') || str_contains($pregunta, 'medic') || str_contains($pregunta, 'cuántos') || str_contains($pregunta, 'cuantos') => 'Acá trabajan cinco médicos en total: dos de clínica médica, una pediatra, un cardiólogo y un traumatólogo. Además están Marta a la mañana y Sofía a la tarde en recepción.',
            str_contains($pregunta, 'turno') || str_contains($pregunta, 'excel') || str_contains($pregunta, 'whatsapp') || str_contains($pregunta, 'problema') => 'Hoy nos piden turno por WhatsApp o llamando por teléfono, y las chicas lo anotan en una planilla de Excel. El problema es que a veces pisan un turno sin querer y caen dos pacientes a la misma hora.',
            str_contains($pregunta, 'obra social') || str_contains($pregunta, 'pago') || str_contains($pregunta, 'cobr') => 'Atendemos tanto particulares como por obra social. Con algunas obras sociales necesitamos pedir una autorización antes de confirmar el turno, y a fin de mes yo cuento a mano cuántos atendió cada médico.',
            str_contains($pregunta, 'presupuesto') || str_contains($pregunta, 'plazo') || str_contains($pregunta, 'tiempo') || str_contains($pregunta, 'cuándo') => 'No tenemos un presupuesto muy grande y no podemos cambiar las computadoras ahora. Nos encantaría tener algo andando antes de marzo, que es cuando se llena de pacientes.',
            default => 'Claro, entiendo lo que me preguntás. Acá el día a día es bastante movido y hacemos casi todo a mano entre el WhatsApp y las planillas. ¿Qué detalle puntual te gustaría que te cuente sobre cómo trabajamos?',
        };

        return new RespuestaIA(
            texto: $texto,
            modelo: 'fake-local',
            tokensPrompt: 120,
            tokensRespuesta: 45,
            tokensTotal: 165,
            latenciaMs: 15,
            finishReason: 'STOP',
        );
    }
}
