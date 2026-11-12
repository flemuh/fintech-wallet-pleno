<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidacaoMensagensTest extends TestCase
{
    use RefreshDatabase;

    public function test_campos_obrigatorios_do_registro_vem_em_portugues(): void
    {
        $this->postJson('/api/auth/register', [])
            ->assertStatus(422)
            ->assertJsonPath('message', 'O campo nome é obrigatório. (e mais 2 erros)')
            ->assertJsonPath('errors.name.0', 'O campo nome é obrigatório.')
            ->assertJsonPath('errors.email.0', 'O campo e-mail é obrigatório.')
            ->assertJsonPath('errors.password.0', 'O campo senha é obrigatório.');
    }

    public function test_senha_sem_numero_e_email_repetido_vem_em_portugues(): void
    {
        User::factory()->create(['email' => 'ja@existe.com']);

        $this->postJson('/api/auth/register', [
            'name' => 'Fulano',
            'email' => 'ja@existe.com',
            'password' => 'Somente@letras',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'O e-mail informado já está em uso.')
            ->assertJsonPath('errors.password.0', 'O campo senha deve conter pelo menos um número.');
    }

    public function test_valor_invalido_vem_em_portugues(): void
    {
        $carteira = Wallet::factory()->create();
        $this->actingAs($carteira->user, 'sanctum');

        $this->postJson('/api/wallet/deposits', ['amount' => 10.999])
            ->assertStatus(422)
            ->assertJsonPath('errors.amount.0', 'O campo valor deve ter no máximo duas casas decimais.');

        $this->postJson('/api/wallet/deposits', ['amount' => 0])
            ->assertStatus(422)
            ->assertJsonPath('errors.amount.0', 'O campo valor deve ser maior que 0.');
    }

    public function test_periodo_invertido_no_historico_vem_em_portugues(): void
    {
        $carteira = Wallet::factory()->create();

        $this->actingAs($carteira->user, 'sanctum')
            ->getJson('/api/wallet/transactions?from=2026-02-10&to=2026-02-01')
            ->assertStatus(422)
            ->assertJsonPath('errors.to.0', 'O campo data final deve ser uma data igual ou posterior a data inicial.');
    }
}
