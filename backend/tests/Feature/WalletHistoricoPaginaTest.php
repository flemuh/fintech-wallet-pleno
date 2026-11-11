<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WalletHistoricoPaginaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function paginasInvalidas(): array
    {
        return [
            'negativa' => ['-1'],
            'zero' => ['0'],
            'texto' => ['abc'],
            'fracionada' => ['1.5'],
        ];
    }

    #[DataProvider('paginasInvalidas')]
    public function test_pagina_invalida_retorna_422(string $pagina): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/wallet/transactions?page={$pagina}")
            ->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonValidationErrors(['page']);
    }
}
