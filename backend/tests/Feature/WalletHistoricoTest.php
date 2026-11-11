<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletHistoricoTest extends TestCase
{
    use RefreshDatabase;

    public function test_historico_lista_so_as_movimentacoes_do_proprio_usuario(): void
    {
        $carteira = Wallet::factory()->create();
        $outra = Wallet::factory()->create();
        Transaction::factory()->count(2)->for($carteira)->create();
        Transaction::factory()->count(3)->for($outra)->create();

        $this->actingAs($carteira->user, 'sanctum')
            ->getJson('/api/wallet/transactions')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);
    }

    public function test_historico_vem_da_mais_recente_para_a_mais_antiga(): void
    {
        $carteira = Wallet::factory()->create();
        $antiga = Transaction::factory()->for($carteira)->create(['created_at' => '2026-01-01 10:00:00']);
        $recente = Transaction::factory()->for($carteira)->create(['created_at' => '2026-01-02 10:00:00']);

        $this->actingAs($carteira->user, 'sanctum')
            ->getJson('/api/wallet/transactions')
            ->assertOk()
            ->assertJsonPath('data.0.id', $recente->id)
            ->assertJsonPath('data.1.id', $antiga->id);
    }

    public function test_filtro_por_tipo(): void
    {
        $carteira = Wallet::factory()->create();
        Transaction::factory()->count(2)->for($carteira)->create();
        Transaction::factory()->for($carteira)->create(['type' => TransactionType::Debit]);

        $this->actingAs($carteira->user, 'sanctum')
            ->getJson('/api/wallet/transactions?type=debit')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'debit');
    }

    public function test_filtro_por_periodo_inclui_o_dia_final_inteiro(): void
    {
        $carteira = Wallet::factory()->create();
        Transaction::factory()->for($carteira)->create(['created_at' => '2026-01-10 12:00:00']);
        $inicio = Transaction::factory()->for($carteira)->create(['created_at' => '2026-01-15 08:00:00']);
        $fim = Transaction::factory()->for($carteira)->create(['created_at' => '2026-01-18 23:30:00']);
        Transaction::factory()->for($carteira)->create(['created_at' => '2026-01-20 12:00:00']);

        $this->actingAs($carteira->user, 'sanctum')
            ->getJson('/api/wallet/transactions?from=2026-01-15&to=2026-01-18')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $fim->id)
            ->assertJsonPath('data.1.id', $inicio->id);
    }

    public function test_paginacao_respeita_per_page(): void
    {
        $carteira = Wallet::factory()->create();
        Transaction::factory()->count(5)->for($carteira)->create();

        $this->actingAs($carteira->user, 'sanctum')
            ->getJson('/api/wallet/transactions?per_page=2&page=3')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_filtros_invalidos_retornam_422(): void
    {
        $carteira = Wallet::factory()->create();
        $this->actingAs($carteira->user, 'sanctum');

        $casos = [
            ['type=transfer', 'type'],
            ['from=10/01/2026', 'from'],
            ['from=2026-02-01&to=2026-01-01', 'to'],
            ['per_page=0', 'per_page'],
            ['per_page=100', 'per_page'],
        ];

        foreach ($casos as [$consulta, $campo]) {
            $this->getJson('/api/wallet/transactions?'.$consulta)
                ->assertStatus(422)
                ->assertJsonValidationErrors($campo);
        }
    }

    public function test_historico_sem_token_retorna_401(): void
    {
        $this->getJson('/api/wallet/transactions')->assertUnauthorized();
    }
}
