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

    /** A chave vem só do cabeçalho: um campo de mesmo nome no corpo é sobrescrito. */
    protected function prepareForValidation(): void
    {
        $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            // Valor em reais, positivo, com no máximo 2 casas decimais
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999.99'],
            // Opcional; um UUID gerado pelo cliente para cada operação
            'idempotency_key' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/'],
        ];
    }

    /** Converte o valor validado de reais para centavos. */
    public function valorEmCentavos(): int
    {
        return (int) round(((float) $this->validated('amount')) * 100);
    }

    public function chaveDeIdempotencia(): ?string
    {
        $chave = $this->validated('idempotency_key');

        return is_string($chave) ? $chave : null;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['amount' => 'valor', 'idempotency_key' => 'chave de idempotência'];
    }
}
