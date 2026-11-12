<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Limita o tamanho em bytes, não em caracteres: acentos, ç e outros caracteres
 * fora do ASCII ocupam mais de um byte cada. Usado na senha, porque o bcrypt só considera 72 bytes.
 */
final readonly class MaximoDeBytes implements ValidationRule
{
    public function __construct(private int $limite) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && strlen($value) > $this->limite) {
            $fail('O campo :attribute ficou longo demais. Use menos acentos ou caracteres especiais.');
        }
    }
}
