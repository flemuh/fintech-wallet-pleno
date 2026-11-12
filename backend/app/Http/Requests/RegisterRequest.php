<?php

namespace App\Http\Requests;

use App\Rules\MaximoDeBytes;
use App\Services\AuthService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc,filter', 'max:255', 'unique:users,email'],
            // Até 64 caracteres, como pedem o NIST e a OWASP, sem passar dos 72 bytes do bcrypt
            'password' => [
                'required',
                'string',
                'max:64',
                new MaximoDeBytes(AuthService::LIMITE_DE_BYTES_DA_SENHA),
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ];
    }
}
