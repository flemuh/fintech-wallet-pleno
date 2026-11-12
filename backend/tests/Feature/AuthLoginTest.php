<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    private function criarUsuario(): User
    {
        return User::factory()->create([
            'email' => 'ana@example.com',
            'password' => 'senha1234',
        ]);
    }

    public function test_login_com_credenciais_corretas_retorna_token(): void
    {
        $this->criarUsuario();

        $this->postJson('/api/auth/login', ['email' => 'ana@example.com', 'password' => 'senha1234'])
            ->assertOk()
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);
    }

    public function test_login_com_senha_errada_retorna_422(): void
    {
        $this->criarUsuario();

        $this->postJson('/api/auth/login', ['email' => 'ana@example.com', 'password' => 'errada123'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_login_com_email_inexistente_retorna_422(): void
    {
        $this->postJson('/api/auth/login', ['email' => 'ninguem@example.com', 'password' => 'senha1234'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_login_recusa_email_invalido_antes_de_consultar_o_banco(): void
    {
        $this->postJson('/api/auth/login', ['email' => '@semusuario', 'password' => 'senha1234'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_login_com_senha_curta_responde_so_credenciais_invalidas(): void
    {
        $this->criarUsuario();

        $this->postJson('/api/auth/login', ['email' => 'ana@example.com', 'password' => 'curta'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Credenciais inválidas.')
            ->assertJsonMissingValidationErrors('password');
    }

    public function test_login_nao_aceita_senha_que_so_bate_nos_primeiros_72_bytes(): void
    {
        $senha = str_repeat('x', 72);
        User::factory()->create(['email' => 'ana@example.com', 'password' => $senha]);

        // O bcrypt ignoraria o que vem depois do byte 72 e aceitaria esta senha
        $this->postJson('/api/auth/login', ['email' => 'ana@example.com', 'password' => $senha.'outra-coisa'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Credenciais inválidas.');
    }

    public function test_logout_revoga_o_token(): void
    {
        $usuario = $this->criarUsuario();
        $token = $usuario->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson('/api/auth/logout')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_rota_protegida_sem_token_retorna_401(): void
    {
        $this->postJson('/api/auth/logout')->assertUnauthorized();
    }
}
