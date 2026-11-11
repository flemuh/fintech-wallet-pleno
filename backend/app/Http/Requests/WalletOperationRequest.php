<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validação compartilhada por depósito e saque. */
class WalletOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            // Valor em reais, positivo, com no máximo 2 casas decimais
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999.99'],
        ];
    }

    /** Converte o valor validado de reais para centavos. */
    public function valorEmCentavos(): int
    {
        return (int) round(((float) $this->validated('amount')) * 100);
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['amount' => 'valor'];
    }
}
