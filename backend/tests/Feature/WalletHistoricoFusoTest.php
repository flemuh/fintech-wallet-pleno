<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletHistoricoFusoTest extends TestCase
{
    use RefreshDatabase;

    // 01:53 de 12/11 em UTC é 22:53 de 11/11 em São Paulo, o dia que a tela mostra
    public function test_filtro_por_periodo_usa_o_dia_de_sao_paulo(): void
    {
        $carteira = Wallet::factory()->create();
        $noiteDoDia11 = Transaction::factory()->for($carteira)->create(['created_at' => '2026-11-12 01:53:00']);

        $this->actingAs($carteira->user, 'sanctum')
            ->getJson('/api/wallet/transactions?from=2026-11-12&to=2026-11-20')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs($carteira->user, 'sanctum')
            ->getJson('/api/wallet/transactions?from=2026-11-11&to=2026-11-11')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $noiteDoDia11->id);
    }
}
