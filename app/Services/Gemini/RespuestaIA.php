<?php

namespace App\Services\Gemini;

/**
 * Objeto de transferencia (DTO) con la respuesta limpia de la IA y sus metadatos.
 */
readonly class RespuestaIA
{
    public function __construct(
        public string $texto,
        public string $modelo,
        public ?int $tokensPrompt = null,
        public ?int $tokensRespuesta = null,
        public ?int $tokensTotal = null,
        public ?int $latenciaMs = null,
        public ?string $finishReason = null,
    ) {}

    public function aMetadata(): array
    {
        return array_filter([
            'modelo' => $this->modelo,
            'tokens_prompt' => $this->tokensPrompt,
            'tokens_respuesta' => $this->tokensRespuesta,
            'tokens_total' => $this->tokensTotal,
            'latencia_ms' => $this->latenciaMs,
            'finish_reason' => $this->finishReason,
        ], fn ($v) => $v !== null);
    }
}
