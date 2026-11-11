<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class InsufficientBalanceException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Saldo insuficiente.');
    }

    /** Resposta 422 com código fixo para o frontend tratar sem comparar texto. */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => 'insufficient_balance',
        ], 422);
    }
}
