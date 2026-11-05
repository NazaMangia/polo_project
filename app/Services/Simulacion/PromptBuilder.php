<?php

namespace App\Services\Simulacion;

use App\Enums\Dificultad;
use App\Enums\PoliticaRevelacion;
use App\Enums\RolMensaje;
use App\Models\Escenario;
use App\Models\Mensaje;
use App\Models\Sesion;
use Illuminate\Support\Collection;

/**
 * Construye el contexto que se envía a Gemini separando claramente las 5 capas:
 *  1. Instrucciones generales del simulador (resources/prompts/simulador_base.md).
 *  2. Perfil y contexto del cliente (rol, personalidad, contexto, problema).
 *  3. Conocimiento del escenario clasificado por política de revelación.
 *  4. Comportamiento según dificultad e instrucciones específicas del escenario.
 *  5. Historial de conversación formateado para el proveedor de IA.
 */
class PromptBuilder
{
    /**
     * Construye el prompt de sistema (capas 1 a 4) a partir de una Sesión (usando su snapshot)
     * o directamente desde un modelo Escenario (para la vista previa del docente o el comando CLI).
     */
    public function construirInstruccionSistema(Sesion|Escenario|array $origen): string
    {
        $datos = match (true) {
            $origen instanceof Sesion => $origen->escenario_snapshot ?: $origen->escenario->aSnapshot(),
            $origen instanceof Escenario => $origen->aSnapshot(),
            default => $origen,
        };

        $bloques = [
            $this->capaInstruccionesGenerales(),
            $this->capaPerfilCliente($datos),
            $this->capaConocimientoEscenario($datos),
            $this->capaComportamientoYDificultad($datos),
        ];

        return implode("\n\n---\n\n", array_filter($bloques));
    }

    /**
     * Capa 5: convierte los mensajes de la sesión al formato esperado por ProveedorIA.
     * Excluye mensajes de rol 'sistema'.
     *
     * @param  Collection<int, Mensaje>|array<int, array{rol: string, contenido: string}>  $mensajes
     * @return array<int, array{rol: string, contenido: string}>
     */
    public function construirHistorial(Collection|array $mensajes): array
    {
        $items = $mensajes instanceof Collection ? $mensajes->all() : $mensajes;
        $historial = [];

        foreach ($items as $mensaje) {
            $rol = $mensaje instanceof Mensaje ? $mensaje->rol->value : ($mensaje['rol'] ?? '');
            $contenido = $mensaje instanceof Mensaje ? $mensaje->contenido : ($mensaje['contenido'] ?? '');

            if ($rol === RolMensaje::Sistema->value || trim($contenido) === '') {
                continue;
            }

            $historial[] = [
                'rol' => $rol,
                'contenido' => trim($contenido),
            ];
        }

        return $historial;
    }

    private function capaInstruccionesGenerales(): string
    {
        $ruta = resource_path('prompts/simulador_base.md');

        return is_file($ruta) ? trim((string) file_get_contents($ruta)) : '';
    }

    private function capaPerfilCliente(array $datos): string
    {
        $lineas = [
            '# TU IDENTIDAD Y CONTEXTO (EL CLIENTE)',
            '- **Escenario:** '.($datos['nombre'] ?? 'Sin nombre'),
            '- **Quién sos (tu rol):** '.($datos['rol_cliente'] ?? 'Cliente'),
        ];

        if (! empty($datos['personalidad'])) {
            $lineas[] = '- **Tu personalidad:** '.$datos['personalidad'];
        }

        if (! empty($datos['contexto'])) {
            $lineas[] = '- **Contexto de tu organización:** '.$datos['contexto'];
        }

        if (! empty($datos['problema_principal'])) {
            $lineas[] = '- **Problema principal que percibís:** '.$datos['problema_principal'];
        }

        return implode("\n", $lineas);
    }

    private function capaConocimientoEscenario(array $datos): string
    {
        $secciones = ['# INFORMACIÓN QUE CONOCÉS SOBRE TU NEGOCIO'];
        $secciones[] = 'Recordá respetar la etiqueta de cada punto antes de mencionarlo:';

        foreach (Escenario::LISTAS_CONOCIMIENTO as $campo => $titulo) {
            $items = $datos[$campo] ?? [];
            if (! is_array($items) || empty($items)) {
                continue;
            }

            $lineas = ["\n## {$titulo}"];
            foreach ($items as $item) {
                $texto = trim((string) ($item['texto'] ?? ''));
                if ($texto === '') {
                    continue;
                }
                $etiqueta = $this->etiquetaRevelacion($item['revelacion'] ?? null);
                $lineas[] = "- {$etiqueta} {$texto}";
            }

            if (count($lineas) > 1) {
                $secciones[] = implode("\n", $lineas);
            }
        }

        if (! empty($datos['informacion_reservada'])) {
            $secciones[] = "\n## Información reservada / secretos del cliente (NO revelar espontáneamente)\n".trim($datos['informacion_reservada']);
        }

        return implode("\n", $secciones);
    }

    private function capaComportamientoYDificultad(array $datos): string
    {
        $dificultadValor = $datos['dificultad'] ?? Dificultad::Basica->value;
        $dificultad = $dificultadValor instanceof Dificultad
            ? $dificultadValor
            : (Dificultad::tryFrom((string) $dificultadValor) ?? Dificultad::Basica);

        $lineas = [
            '# COMPORTAMIENTO DURANTE LA ENTREVISTA',
            '- **Nivel de dificultad ('.$dificultad->etiqueta().'):** '.$dificultad->comportamiento(),
        ];

        if (! empty($datos['comportamiento_esperado'])) {
            $lineas[] = '- **Pautas de comportamiento en este escenario:** '.trim($datos['comportamiento_esperado']);
        }

        if (! empty($datos['instrucciones_ia'])) {
            $lineas[] = '- **Instrucciones adicionales del docente:** '.trim($datos['instrucciones_ia']);
        }

        return implode("\n", $lineas);
    }

    private function etiquetaRevelacion(?string $valor): string
    {
        return match ($valor) {
            PoliticaRevelacion::Espontaneo->value => '[ESPONTÁNEO]',
            PoliticaRevelacion::MuyEspecifico->value => '[PREGUNTA PRECISA]',
            default => '[SI PREGUNTAN]',
        };
    }
}
