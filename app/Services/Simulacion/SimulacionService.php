<?php

namespace App\Services\Simulacion;

use App\Contracts\ProveedorIA;
use App\Enums\EstadoSesion;
use App\Enums\RolMensaje;
use App\Models\Equipo;
use App\Models\Mensaje;
use App\Models\Sesion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Orquesta el ciclo de vida de una sesión de entrevista del alumno:
 *  - validar el código de equipo y su escenario asignado;
 *  - iniciar una sesión nueva o retomar la sesión activa existente;
 *  - procesar preguntas del alumno llamando a Gemini y guardando ambos mensajes en transacción;
 *  - finalizar la entrevista.
 */
class SimulacionService
{
    public function __construct(
        private readonly PromptBuilder $promptBuilder,
        private readonly ProveedorIA $proveedorIA,
    ) {}

    /**
     * Inicia una nueva sesión para el equipo o retoma la que ya tenga activa
     * para el escenario actualmente asignado.
     *
     * @return array{sesion: Sesion, token: string, reanudada: bool}
     */
    public function iniciarOReanudarPorCodigo(string $codigo): array
    {
        $equipo = Equipo::with('escenario')->where('codigo', Equipo::normalizarCodigo($codigo))->first();

        if (! $equipo) {
            throw ValidationException::withMessages([
                'codigo' => ['El código de equipo ingresado no existe. Verificá que esté bien escrito.'],
            ]);
        }

        if (! $equipo->activo) {
            throw ValidationException::withMessages([
                'codigo' => ['Este equipo está deshabilitado. Consultá con el docente.'],
            ]);
        }

        $escenario = $equipo->escenario;

        if (! $escenario || ! $escenario->activo) {
            throw ValidationException::withMessages([
                'codigo' => ['Tu equipo todavía no tiene un escenario activo asignado por el docente.'],
            ]);
        }

        return DB::transaction(function () use ($equipo, $escenario) {
            // Buscamos si ya tiene una sesión activa para este escenario.
            $sesionExistente = Sesion::where('equipo_id', $equipo->id)
                ->where('escenario_id', $escenario->id)
                ->where('estado', EstadoSesion::Activa)
                ->lockForUpdate()
                ->first();

            if ($sesionExistente) {
                $token = $sesionExistente->generarToken();
                $sesionExistente->save();

                return [
                    'sesion' => $sesionExistente->load(['equipo', 'escenario', 'mensajes']),
                    'token' => $token,
                    'reanudada' => true,
                ];
            }

            $sesion = new Sesion([
                'equipo_id' => $equipo->id,
                'escenario_id' => $escenario->id,
                'escenario_snapshot' => $escenario->aSnapshot(),
                'inicio' => now(),
                'estado' => EstadoSesion::Activa,
            ]);

            $token = $sesion->generarToken();
            $sesion->save();

            // El primer mensaje de la conversación es el saludo inicial del cliente configurado en el escenario.
            $sesion->mensajes()->create([
                'rol' => RolMensaje::Cliente,
                'contenido' => $escenario->mensaje_inicial,
                'metadata' => ['tipo' => 'mensaje_inicial'],
            ]);

            return [
                'sesion' => $sesion->load(['equipo', 'escenario', 'mensajes']),
                'token' => $token,
                'reanudada' => false,
            ];
        });
    }

    /**
     * Registra la pregunta del alumno, consulta al proveedor de IA y guarda la respuesta del cliente.
     * Si la IA falla, no se guarda ningún mensaje para que el alumno pueda reintentar limpiamente.
     *
     * @return array{mensaje_alumno: Mensaje, mensaje_cliente: Mensaje}
     */
    public function enviarMensajeAlumno(Sesion $sesion, string $contenido): array
    {
        $lock = Cache::lock("sesion-mensaje:{$sesion->id}", 35);

        if (! $lock->get()) {
            throw new HttpException(429, 'El cliente todavía está respondiendo tu mensaje anterior. Esperá unos segundos.');
        }

        try {
            $sesion->refresh();

            if (! $sesion->estaActiva()) {
                throw new HttpException(409, 'Esta entrevista ya fue finalizada y no admite nuevos mensajes.');
            }

            $maxMensajes = (int) config('gemini.max_mensajes_sesion', 80);
            $cantidadActual = $sesion->mensajes()->count();

            if ($cantidadActual >= $maxMensajes) {
                throw new HttpException(422, "Se alcanzó el límite máximo de {$maxMensajes} mensajes para esta entrevista.");
            }

            $mensajesPrevios = $sesion->mensajes()->get();
            $historial = $this->promptBuilder->construirHistorial($mensajesPrevios);
            $historial[] = [
                'rol' => RolMensaje::Alumno->value,
                'contenido' => trim($contenido),
            ];

            $instruccionSistema = $this->promptBuilder->construirInstruccionSistema($sesion);

            // Llamamos a la IA fuera de la transacción de BD para no retener bloqueos de fila durante la llamada HTTP.
            $respuestaIA = $this->proveedorIA->responder($instruccionSistema, $historial);

            return DB::transaction(function () use ($sesion, $contenido, $respuestaIA) {
                $mensajeAlumno = $sesion->mensajes()->create([
                    'rol' => RolMensaje::Alumno,
                    'contenido' => trim($contenido),
                ]);

                $mensajeCliente = $sesion->mensajes()->create([
                    'rol' => RolMensaje::Cliente,
                    'contenido' => $respuestaIA->texto,
                    'metadata' => $respuestaIA->aMetadata(),
                ]);

                return [
                    'mensaje_alumno' => $mensajeAlumno,
                    'mensaje_cliente' => $mensajeCliente,
                ];
            });
        } finally {
            $lock->release();
        }
    }

    public function finalizarSesion(Sesion $sesion, string $motivo = 'Entrevista finalizada por el equipo.'): Sesion
    {
        if (! $sesion->estaActiva()) {
            return $sesion;
        }

        DB::transaction(function () use ($sesion, $motivo) {
            $sesion->finalizar();

            $sesion->mensajes()->create([
                'rol' => RolMensaje::Sistema,
                'contenido' => $motivo,
            ]);
        });

        return $sesion->refresh();
    }
}
