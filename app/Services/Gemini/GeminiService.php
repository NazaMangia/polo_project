<?php

namespace App\Services\Gemini;

use App\Contracts\ProveedorIA;
use App\Enums\RolMensaje;
use App\Exceptions\ProveedorIAException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente para Google Gemini API (REST v1beta).
 * La API Key se lee desde config('gemini.api_key') y nunca sale del servidor.
 */
class GeminiService implements ProveedorIA
{
    public function responder(string $instruccionSistema, array $historial): RespuestaIA
    {
        $apiKey = (string) config('gemini.api_key');

        if ($apiKey === '') {
            throw new ProveedorIAException(
                message: 'Falta configurar GEMINI_API_KEY en el servidor.',
                statusHttp: 500,
                detalleTecnico: 'GEMINI_API_KEY vacía.',
            );
        }

        $modelo = (string) config('gemini.model', 'gemini-2.5-flash');
        $baseUrl = rtrim((string) config('gemini.base_url'), '/');
        $endpoint = "{$baseUrl}/models/{$modelo}:generateContent";

        $payload = [
            'system_instruction' => [
                'parts' => [
                    ['text' => $instruccionSistema],
                ],
            ],
            'contents' => $this->formatearContenidos($historial),
            'generationConfig' => [
                'temperature' => (float) config('gemini.temperature', 0.7),
                'maxOutputTokens' => (int) config('gemini.max_output_tokens', 600),
            ],
        ];

        $inicio = hrtime(true);

        try {
            $response = Http::timeout((int) config('gemini.timeout', 30))
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->retry(2, 600, function ($exception) {
                    // Reintentar solo ante errores de red o saturación temporal (429 / 503).
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }
                    $status = method_exists($exception, 'getCode') ? (int) $exception->getCode() : 0;

                    return in_array($status, [429, 503], true);
                }, throw: false)
                ->post($endpoint, $payload);
        } catch (ConnectionException $e) {
            Log::warning('Error de conexión con Gemini API', ['error' => $e->getMessage()]);

            throw new ProveedorIAException(
                message: 'No se pudo conectar con el cliente virtual. Intentá nuevamente en unos segundos.',
                statusHttp: 503,
                detalleTecnico: $e->getMessage(),
                previous: $e,
            );
        }

        $latenciaMs = (int) round((hrtime(true) - $inicio) / 1_000_000);

        if ($response->failed()) {
            $status = $response->status();
            $detalle = (string) $response->json('error.message', $response->body());

            Log::warning('Respuesta de error desde Gemini API', [
                'status' => $status,
                'detalle' => $detalle,
            ]);

            $mensajeUsuario = match ($status) {
                429 => 'El cliente está atendiendo muchas consultas en este momento (límite de cuota). Esperá unos segundos y volvé a enviar tu pregunta.',
                400, 401, 403 => 'La configuración de la API de IA no es válida. Avisale al docente.',
                default => 'El cliente virtual no pudo responder en este momento. Intentá nuevamente en unos segundos.',
            };

            throw new ProveedorIAException(
                message: $mensajeUsuario,
                statusHttp: $status,
                detalleTecnico: $detalle,
            );
        }

        $json = $response->json();
        $partes = data_get($json, 'candidates.0.content.parts', []);
        $texto = collect($partes)->pluck('text')->filter()->implode("\n");
        $finishReason = data_get($json, 'candidates.0.finishReason');

        if (trim($texto) === '') {
            Log::warning('Gemini devolvió una respuesta vacía o bloqueada', [
                'finishReason' => $finishReason,
            ]);

            throw new ProveedorIAException(
                message: 'El cliente no pudo elaborar la respuesta. Intentá reformular tu pregunta.',
                statusHttp: 502,
                detalleTecnico: 'Respuesta vacía. finishReason: '.($finishReason ?? 'null'),
            );
        }

        return new RespuestaIA(
            texto: trim($texto),
            modelo: $modelo,
            tokensPrompt: data_get($json, 'usageMetadata.promptTokenCount'),
            tokensRespuesta: data_get($json, 'usageMetadata.candidatesTokenCount'),
            tokensTotal: data_get($json, 'usageMetadata.totalTokenCount'),
            latenciaMs: $latenciaMs,
            finishReason: $finishReason,
        );
    }

    /**
     * Convierte el historial interno a la estructura `contents` que exige Gemini.
     * Gemini requiere que el primer turno tenga rol `user`; si la sesión comenzó
     * con el saludo del cliente (`model`), anteponemos un turno `user` sintético.
     */
    public function formatearContenidos(array $historial): array
    {
        $contents = [];

        foreach ($historial as $item) {
            $rol = ($item['rol'] ?? '') === RolMensaje::Cliente->value ? 'model' : 'user';
            $texto = trim((string) ($item['contenido'] ?? ''));

            if ($texto === '') {
                continue;
            }

            if (empty($contents) && $rol === 'model') {
                $contents[] = [
                    'role' => 'user',
                    'parts' => [['text' => '[Comienza la entrevista de relevamiento]']],
                ];
            }

            $contents[] = [
                'role' => $rol,
                'parts' => [['text' => $texto]],
            ];
        }

        if (empty($contents)) {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => 'Hola']],
            ];
        }

        return $contents;
    }
}
