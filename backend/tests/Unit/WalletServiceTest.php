<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\WalletService;
use InvalidArgumentException;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    public function test_deposito_com_valor_zero_ou_negativo_lanca_excecao(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new WalletService)->depositar(new User, 0);
    }

    public function test_saque_com_valor_negativo_lanca_excecao(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new WalletService)->sacar(new User, -100);
    }
}
