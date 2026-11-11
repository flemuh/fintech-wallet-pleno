<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

/** Operação abortada pelo banco por concorrência mesmo após as novas tentativas. */
class ConcurrentOperationException extends RuntimeException
{
    public function __construct(?Throwable $anterior = null)
    {
        parent::__construct('Operação concorrente, tente novamente.', 0, $anterior);
    }

    /** O Laravel chama este método para montar a resposta HTTP. */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => 'concurrent_operation',
        ], 409);
    }
}
