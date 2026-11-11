<?php

namespace Tests\Feature;

use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletSaqueTest extends TestCase
{
    use RefreshDatabase;

    public function test_saque_diminui_saldo_e_grava_debito(): void
    {
        $carteira = Wallet::factory()->create(['balance' => 10000]);

        $this->actingAs($carteira->user, 'sanctum')
            ->postJson('/api/wallet/withdrawals', ['amount' => 30.50])
            ->assertCreated()
            ->assertJsonPath('data.type', 'debit')
            ->assertJsonPath('data.amount', 3050)
            ->assertJsonPath('data.balance_after', 6950);

        $this->assertEquals(6950, $carteira->fresh()->balance);
        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $carteira->id,
            'type' => 'debit',
            'amount' => 3050,
            'balance_after' => 6950,
        ]);
    }

    public function test_saque_do_saldo_exato_zera_a_carteira(): void
    {
        $carteira = Wallet::factory()->create(['balance' => 5000]);

        $this->actingAs($carteira->user, 'sanctum')
            ->postJson('/api/wallet/withdrawals', ['amount' => 50])
            ->assertCreated()
            ->assertJsonPath('data.balance_after', 0);

        $this->assertEquals(0, $carteira->fresh()->balance);
    }

    public function test_saque_maior_que_o_saldo_retorna_422_e_nada_muda(): void
    {
        $carteira = Wallet::factory()->create(['balance' => 5000]);

        $this->actingAs($carteira->user, 'sanctum')
            ->postJson('/api/wallet/withdrawals', ['amount' => 50.01])
            ->assertStatus(422)
            ->assertJson(['code' => 'insufficient_balance']);

        $this->assertEquals(5000, $carteira->fresh()->balance);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_saque_com_valor_invalido_retorna_422(): void
    {
        $carteira = Wallet::factory()->create(['balance' => 5000]);
        $this->actingAs($carteira->user, 'sanctum');

        foreach ([0, -10, 10.999, 'abc', null] as $valor) {
            $this->postJson('/api/wallet/withdrawals', ['amount' => $valor])
                ->assertStatus(422)
                ->assertJsonValidationErrors('amount');
        }

        $this->assertEquals(5000, $carteira->fresh()->balance);
    }

    public function test_saque_sem_token_retorna_401(): void
    {
        $this->postJson('/api/wallet/withdrawals', ['amount' => 10])->assertUnauthorized();
    }
}
