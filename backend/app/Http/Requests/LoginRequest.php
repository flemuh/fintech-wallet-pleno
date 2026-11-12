<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * A senha não repete as regras do cadastro: qualquer senha errada,
     * curta ou longa, recebe a mesma resposta genérica do AuthService.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc,filter', 'max:255'],
            // O máximo só evita processar textos enormes
            'password' => ['required', 'string', 'max:255'],
        ];
    }
}
