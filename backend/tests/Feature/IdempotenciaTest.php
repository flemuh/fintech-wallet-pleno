<?php

namespace Tests\Feature;

use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class IdempotenciaTest extends TestCase
{
    use RefreshDatabase;

    private const CHAVE = '0b6f3c1e-9a7d-4c2b-8e5f-1d2a3b4c5d6e';

    /** @return TestResponse<JsonResponse> */
    private function operar(string $rota, float $valor, ?string $chave = self::CHAVE): TestResponse
    {
        $cabecalhos = $chave === null ? [] : ['Idempotency-Key' => $chave];

        return $this->postJson("/api/wallet/{$rota}", ['amount' => $valor], $cabecalhos);
    }

    public function test_deposito_repetido_com_a_mesma_chave_nao_e_executado_de_novo(): void
    {
        $carteira = Wallet::factory()->create();
        $this->actingAs($carteira->user, 'sanctum');

        $primeira = $this->operar('deposits', 10)->assertCreated()->assertHeaderMissing('Idempotent-Replayed');
        $this->operar('deposits', 10)
            ->assertCreated()
            ->assertHeader('Idempotent-Replayed', 'true')
            ->assertJsonPath('data.id', $primeira->json('data.id'));

        $this->assertEquals(1000, $carteira->fresh()->balance);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_saque_repetido_com_a_mesma_chave_nao_e_executado_de_novo(): void
    {
        $carteira = Wallet::factory()->create(['balance' => 5000]);
        $this->actingAs($carteira->user, 'sanctum');

        $this->operar('withdrawals', 20)->assertCreated();
        $this->operar('withdrawals', 20)->assertCreated()->assertHeader('Idempotent-Replayed', 'true');

        $this->assertEquals(3000, $carteira->fresh()->balance);
    }

    public function test_mesma_chave_com_outro_valor_e_recusada_sem_mudar_o_saldo(): void
    {
        $carteira = Wallet::factory()->create();
        $this->actingAs($carteira->user, 'sanctum');

        $this->operar('deposits', 10)->assertCreated();
        $this->operar('deposits', 99)
            ->assertStatus(422)
            ->assertExactJson([
                'message' => 'Esta chave de idempotência já foi usada em outra operação.',
                'code' => 'idempotency_key_reused',
            ]);

        $this->assertEquals(1000, $carteira->fresh()->balance);
    }

    public function test_mesma_chave_em_outro_tipo_de_operacao_e_recusada(): void
    {
        $carteira = Wallet::factory()->create(['balance' => 5000]);
        $this->actingAs($carteira->user, 'sanctum');

        $this->operar('deposits', 10)->assertCreated();
        $this->operar('withdrawals', 10)->assertStatus(422)->assertJsonPath('code', 'idempotency_key_reused');

        $this->assertEquals(6000, $carteira->fresh()->balance);
    }

    public function test_a_chave_de_um_usuario_nao_afeta_outro(): void
    {
        $ana = Wallet::factory()->create();
        $bruno = Wallet::factory()->create();

        $this->actingAs($ana->user, 'sanctum');
        $this->operar('deposits', 10)->assertCreated();
        $this->actingAs($bruno->user, 'sanctum');
        $this->operar('deposits', 10)->assertCreated()->assertHeaderMissing('Idempotent-Replayed');

        $this->assertEquals(1000, $bruno->fresh()->balance);
    }

    public function test_operacao_recusada_nao_gasta_a_chave(): void
    {
        $carteira = Wallet::factory()->create();
        $this->actingAs($carteira->user, 'sanctum');

        $this->operar('withdrawals', 10)->assertStatus(422)->assertJsonPath('code', 'insufficient_balance');
        $this->operar('deposits', 50, chave: null)->assertCreated();
        $this->operar('withdrawals', 10)->assertCreated()->assertHeaderMissing('Idempotent-Replayed');

        $this->assertEquals(4000, $carteira->fresh()->balance);
    }

    public function test_sem_chave_cada_requisicao_e_uma_operacao(): void
    {
        $carteira = Wallet::factory()->create();
        $this->actingAs($carteira->user, 'sanctum');

        $this->operar('deposits', 10, chave: null)->assertCreated();
        $this->operar('deposits', 10, chave: null)->assertCreated();

        $this->assertEquals(2000, $carteira->fresh()->balance);
    }

    public function test_chave_fora_do_formato_e_recusada(): void
    {
        $carteira = Wallet::factory()->create();
        $this->actingAs($carteira->user, 'sanctum');

        $this->operar('deposits', 10, chave: 'tem espaço')
            ->assertStatus(422)
            ->assertJsonPath('errors.idempotency_key.0', 'O formato do campo chave de idempotência é inválido.');
        $this->operar('deposits', 10, chave: str_repeat('a', 101))
            ->assertStatus(422)
            ->assertJsonValidationErrors('idempotency_key');

        $this->assertDatabaseCount('transactions', 0);
    }
}
