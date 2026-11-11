<?php

namespace Tests\Feature;

use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrosJsonTest extends TestCase
{
    use RefreshDatabase;

    public function test_rota_inexistente_retorna_404_em_json(): void
    {
        $this->getJson('/api/nao-existe')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Recurso não encontrado.', 'code' => 'not_found']);
    }

    public function test_404_responde_json_mesmo_sem_cabecalho_accept(): void
    {
        $this->get('/api/nao-existe')
            ->assertNotFound()
            ->assertJson(['code' => 'not_found']);
    }

    public function test_rota_protegida_sem_token_e_sem_accept_retorna_401_em_json(): void
    {
        $this->get('/api/wallet')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Não autenticado.', 'code' => 'unauthenticated']);
    }

    public function test_metodo_nao_permitido_retorna_405_em_json(): void
    {
        $this->postJson('/api/health')
            ->assertStatus(405)
            ->assertJson(['code' => 'method_not_allowed']);
    }

    public function test_erro_de_validacao_retorna_422_com_codigo_e_erros_por_campo(): void
    {
        $this->postJson('/api/auth/register', [])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'code', 'errors'])
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_registro_e_bloqueado_depois_de_cinco_tentativas_por_minuto(): void
    {
        // IP próprio do teste, para não misturar a contagem com outros testes
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10']);

        foreach (range(1, 5) as $tentativa) {
            $this->postJson('/api/auth/register', [])->assertStatus(422);
        }

        $this->postJson('/api/auth/register', [])
            ->assertStatus(429)
            ->assertJson(['code' => 'too_many_requests'])
            ->assertHeader('Retry-After');
    }

    public function test_login_e_bloqueado_depois_de_cinco_tentativas_por_minuto(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20']);
        $dados = ['email' => 'ninguem@teste.com', 'password' => 'senha-errada'];

        foreach (range(1, 5) as $tentativa) {
            $this->postJson('/api/auth/login', $dados)->assertStatus(422);
        }

        $this->postJson('/api/auth/login', $dados)
            ->assertStatus(429)
            ->assertJson(['code' => 'too_many_requests']);
    }

    public function test_depositos_sao_bloqueados_depois_de_vinte_por_minuto(): void
    {
        $carteira = Wallet::factory()->create();
        $this->actingAs($carteira->user, 'sanctum');

        foreach (range(1, 20) as $tentativa) {
            $this->postJson('/api/wallet/deposits', ['amount' => 1])->assertCreated();
        }

        $this->postJson('/api/wallet/deposits', ['amount' => 1])
            ->assertStatus(429)
            ->assertJson(['code' => 'too_many_requests']);

        $this->assertEquals(2000, $carteira->fresh()->balance);
    }
}
