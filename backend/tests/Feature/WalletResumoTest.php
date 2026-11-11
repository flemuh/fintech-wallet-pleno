<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
                'month_credit' => 0,
                'month_debit' => 0,
            ]]);
    }

    public function test_resumo_reflete_depositos_e_saques_do_mes(): void
    {
        $carteira = Wallet::factory()->create();
        $this->actingAs($carteira->user, 'sanctum');

        $this->postJson('/api/wallet/deposits', ['amount' => 100])->assertCreated();
        $this->postJson('/api/wallet/deposits', ['amount' => 50])->assertCreated();
        $this->postJson('/api/wallet/withdrawals', ['amount' => 30])->assertCreated();

        $this->getJson('/api/wallet')
            ->assertOk()
            ->assertJsonPath('data.balance', 12000)
            ->assertJsonPath('data.month_credit', 15000)
            ->assertJsonPath('data.month_debit', 3000);
    }

    public function test_totais_do_mes_seguem_o_fuso_de_sao_paulo(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00', 'America/Sao_Paulo'));

        $carteira = Wallet::factory()->create();

        // Instantes em horário de São Paulo; o banco guarda em UTC
        $movimentacoes = [
            ['2026-05-31 22:00:00', TransactionType::Credit, 100], // maio: fora
            ['2026-06-01 01:00:00', TransactionType::Credit, 200], // 1º de junho em SP (04h UTC): dentro
            ['2026-06-10 10:00:00', TransactionType::Debit, 50],   // junho: dentro
            ['2026-06-30 23:59:00', TransactionType::Credit, 400], // último minuto de junho em SP: dentro
            ['2026-07-01 00:00:00', TransactionType::Credit, 800], // julho: fora
        ];

        foreach ($movimentacoes as [$instante, $tipo, $valor]) {
            Transaction::factory()->for($carteira)->create([
                'type' => $tipo,
                'amount' => $valor,
                'created_at' => Carbon::parse($instante, 'America/Sao_Paulo')->utc(),
            ]);
        }

        $this->actingAs($carteira->user, 'sanctum')
            ->getJson('/api/wallet')
            ->assertOk()
            ->assertJsonPath('data.month_credit', 600)
            ->assertJsonPath('data.month_debit', 50);
    }

    public function test_resumo_ignora_movimentacoes_de_outras_carteiras(): void
    {
        $carteira = Wallet::factory()->create();
        Transaction::factory()->count(4)->for(Wallet::factory()->create())->create();

        $this->actingAs($carteira->user, 'sanctum')
            ->getJson('/api/wallet')
            ->assertOk()
            ->assertJsonPath('data.month_credit', 0)
            ->assertJsonPath('data.month_debit', 0);
    }

    public function test_resumo_sem_token_retorna_401(): void
    {
        $this->getJson('/api/wallet')->assertUnauthorized();
    }
}
