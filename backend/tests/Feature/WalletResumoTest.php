<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletResumoTest extends TestCase
{
    use RefreshDatabase;

    public function test_carteira_nova_tem_tudo_zerado(): void
    {
        $carteira = Wallet::factory()->create();

        $this->actingAs($carteira->user, 'sanctum')
            ->getJson('/api/wallet')
            ->assertOk()
            ->assertExactJson(['data' => [
                'balance' => 0,
                'total_credit' => 0,
                'total_debit' => 0,
                'transactions_count' => 0,
            ]]);
    }

    public function test_resumo_reflete_depositos_e_saques(): void
    {
        $carteira = Wallet::factory()->create();
        $this->actingAs($carteira->user, 'sanctum');

        $this->postJson('/api/wallet/deposits', ['amount' => 100])->assertCreated();
        $this->postJson('/api/wallet/deposits', ['amount' => 50])->assertCreated();
        $this->postJson('/api/wallet/withdrawals', ['amount' => 30])->assertCreated();

        $this->getJson('/api/wallet')
            ->assertOk()
            ->assertJsonPath('data.balance', 12000)
            ->assertJsonPath('data.total_credit', 15000)
            ->assertJsonPath('data.total_debit', 3000)
            ->assertJsonPath('data.transactions_count', 3);
    }

    public function test_resumo_ignora_movimentacoes_de_outras_carteiras(): void
    {
        $carteira = Wallet::factory()->create();
        Transaction::factory()->count(4)->for(Wallet::factory()->create())->create();

        $this->actingAs($carteira->user, 'sanctum')
            ->getJson('/api/wallet')
            ->assertOk()
            ->assertJsonPath('data.transactions_count', 0)
            ->assertJsonPath('data.total_credit', 0);
    }

    public function test_resumo_sem_token_retorna_401(): void
    {
        $this->getJson('/api/wallet')->assertUnauthorized();
    }
}
