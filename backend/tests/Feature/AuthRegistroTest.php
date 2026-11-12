<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRegistroTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
    private function dadosValidos(): array
    {
        return [
            'name' => 'Ana Souza',
            'email' => 'ana@example.com',
            'password' => 'Senha@1234',
        ];
    }

    public function test_registro_cria_usuario_e_carteira_com_saldo_zero(): void
    {
        $this->postJson('/api/auth/register', $this->dadosValidos())
            ->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token'])
            ->assertJsonMissingPath('user.password');

        $usuario = User::where('email', 'ana@example.com')->firstOrFail();
        $this->assertEquals(0, $usuario->wallet->balance);
    }

    public function test_email_duplicado_retorna_422(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $this->postJson('/api/auth/register', $this->dadosValidos())
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_senha_fraca_retorna_422(): void
    {
        $this->postJson('/api/auth/register', [...$this->dadosValidos(), 'password' => 'abc'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }
}
