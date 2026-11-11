<?php

namespace Tests\Feature;

use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletDepositoTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposito_soma_saldo_e_grava_credito(): void
    {
        $carteira = Wallet::factory()->create();

        $this->actingAs($carteira->user, 'sanctum')
            ->postJson('/api/wallet/deposits', ['amount' => 150.75])
            ->assertCreated()
            ->assertJsonPath('data.type', 'credit')
            ->assertJsonPath('data.amount', 15075)
            ->assertJsonPath('data.balance_after', 15075);

        $this->assertEquals(15075, $carteira->fresh()->balance);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_depositos_seguidos_acumulam_o_saldo(): void
    {
        $carteira = Wallet::factory()->create();
        $this->actingAs($carteira->user, 'sanctum');

        $this->postJson('/api/wallet/deposits', ['amount' => 100])->assertCreated();
        $this->postJson('/api/wallet/deposits', ['amount' => 0.5])
            ->assertCreated()
            ->assertJsonPath('data.balance_after', 10050);

        $this->assertEquals(10050, $carteira->fresh()->balance);
    }

    public function test_valores_invalidos_retornam_422_e_nada_muda(): void
    {
        $carteira = Wallet::factory()->create();
        $this->actingAs($carteira->user, 'sanctum');

        // zero, negativo, 3 casas decimais, texto e ausente
        foreach ([0, -10, 10.999, 'abc', null] as $valor) {
            $this->postJson('/api/wallet/deposits', ['amount' => $valor])
                ->assertStatus(422)
                ->assertJsonValidationErrors('amount');
        }

        $this->assertEquals(0, $carteira->fresh()->balance);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_deposito_sem_token_retorna_401(): void
    {
        $this->postJson('/api/wallet/deposits', ['amount' => 10])->assertUnauthorized();
    }
}
