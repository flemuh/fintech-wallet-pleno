<?php

namespace Tests\Feature;

use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WalletConversaoTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{float, int}> */
    public static function valoresEmReais(): array
    {
        return [
            'um centavo' => [0.01, 1],
            'dez centavos' => [0.10, 10],
            'erro de ponto flutuante: 0,29' => [0.29, 29],
            'erro de ponto flutuante: 1,15' => [1.15, 115],
            'erro de ponto flutuante: 4,35' => [4.35, 435],
            'erro de ponto flutuante: 57,55' => [57.55, 5755],
            'valor com centavos' => [19.99, 1999],
            'valor máximo permitido' => [999999.99, 99999999],
        ];
    }

    #[DataProvider('valoresEmReais')]
    public function test_converte_reais_em_centavos_sem_erro_de_arredondamento(float $reais, int $centavos): void
    {
        $carteira = Wallet::factory()->create();

        $this->actingAs($carteira->user, 'sanctum')
            ->postJson('/api/wallet/deposits', ['amount' => $reais])
            ->assertCreated()
            ->assertJsonPath('data.amount', $centavos);

        $this->assertEquals($centavos, $carteira->fresh()->balance);
    }
}
