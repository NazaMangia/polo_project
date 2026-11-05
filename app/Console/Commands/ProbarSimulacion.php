<?php

namespace App\Console\Commands;

use App\Contracts\ProveedorIA;
use App\Enums\RolMensaje;
use App\Exceptions\ProveedorIAException;
use App\Models\Escenario;
use App\Services\Simulacion\PromptBuilder;
use Illuminate\Console\Command;

/**
 * Permite inspeccionar el prompt que recibe Gemini y conversar con el cliente virtual
 * directamente desde la terminal antes o después de usar la interfaz web.
 *
 * Ejemplos:
 *  php artisan simulador:probar --mostrar-prompt
 *  php artisan simulador:probar --pregunta="¿Cuántos médicos trabajan en la clínica?"
 *  php artisan simulador:probar 1
 */
class ProbarSimulacion extends Command
{
    protected $signature = 'simulador:probar
                            {escenario? : ID del escenario (por defecto usa el primero activo)}
                            {--mostrar-prompt : Muestra el prompt completo de sistema (capas 1 a 4)}
                            {--pregunta= : Envía una única pregunta y termina (modo no interactivo)}';

    protected $description = 'Prueba un escenario conversando con el cliente virtual desde la consola o inspecciona su prompt';

    public function handle(PromptBuilder $promptBuilder, ProveedorIA $proveedorIA): int
    {
        $escenarioId = $this->argument('escenario');

        $escenario = $escenarioId
            ? Escenario::find($escenarioId)
            : Escenario::activos()->first();

        if (! $escenario) {
            $this->error('No se encontró el escenario indicado. Ejecutá `php artisan migrate --seed` primero.');

            return self::FAILURE;
        }

        $instruccionSistema = $promptBuilder->construirInstruccionSistema($escenario);

        if ($this->option('mostrar-prompt')) {
            $this->line($instruccionSistema);

            if (! $this->option('pregunta')) {
                return self::SUCCESS;
            }
            $this->newLine();
        }

        $historial = [
            [
                'rol' => RolMensaje::Cliente->value,
                'contenido' => $escenario->mensaje_inicial,
            ],
        ];

        $this->info("Escenario: {$escenario->nombre} (Dificultad: {$escenario->dificultad->etiqueta()})");
        $this->comment("CLIENTE: {$escenario->mensaje_inicial}");
        $this->newLine();

        $preguntaUnica = $this->option('pregunta');
        if ($preguntaUnica !== null) {
            return $this->enviarTurno($proveedorIA, $instruccionSistema, $historial, (string) $preguntaUnica);
        }

        $this->line('Escribí tus preguntas como entrevistador (o "salir" para terminar):');

        while (true) {
            $pregunta = trim((string) $this->ask('ALUMNO'));

            if ($pregunta === '' || in_array(mb_strtolower($pregunta), ['salir', 'exit', 'fin'], true)) {
                $this->info('Entrevista finalizada.');
                break;
            }

            $this->enviarTurno($proveedorIA, $instruccionSistema, $historial, $pregunta);
        }

        return self::SUCCESS;
    }

    private function enviarTurno(
        ProveedorIA $proveedorIA,
        string $instruccionSistema,
        array &$historial,
        string $pregunta,
    ): int {
        $historial[] = [
            'rol' => RolMensaje::Alumno->value,
            'contenido' => $pregunta,
        ];

        try {
            $respuesta = $proveedorIA->responder($instruccionSistema, $historial);
        } catch (ProveedorIAException $e) {
            array_pop($historial);
            $this->error("ERROR IA: {$e->getMessage()}");

            return self::FAILURE;
        }

        $historial[] = [
            'rol' => RolMensaje::Cliente->value,
            'contenido' => $respuesta->texto,
        ];

        $this->comment("CLIENTE [{$respuesta->modelo}]: {$respuesta->texto}");
        $this->newLine();

        return self::SUCCESS;
    }
}
