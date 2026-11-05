<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class ProveedorIAException extends RuntimeException
{
    public function __construct(
        string $message = 'El cliente virtual no pudo responder en este momento. Intentá nuevamente en unos segundos.',
        public readonly ?int $statusHttp = null,
        public readonly ?string $detalleTecnico = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
