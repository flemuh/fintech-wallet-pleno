<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/** A mesma Idempotency-Key chegou com outro tipo de operação ou outro valor. */
class IdempotencyKeyReusedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Esta chave de idempotência já foi usada em outra operação.');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => 'idempotency_key_reused',
        ], 422);
    }
}
