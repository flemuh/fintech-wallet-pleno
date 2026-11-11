<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpiracaoDoTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_vale_dentro_do_prazo(): void
    {
        $token = User::factory()->create()->createToken('teste')->plainTextToken;

        $this->travel(1439)->minutes();

        $this->withToken($token)->postJson('/api/auth/logout')->assertNoContent();
    }

    public function test_token_expirado_retorna_401(): void
    {
        $token = User::factory()->create()->createToken('teste')->plainTextToken;

        $this->travel(1441)->minutes();

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'unauthenticated');
    }

    public function test_token_novo_usa_o_prefixo(): void
    {
        $token = User::factory()->create()->createToken('teste')->plainTextToken;

        // O Sanctum devolve "id|prefixo + segredo", então o prefixo vem depois da barra
        $this->assertMatchesRegularExpression('/^\d+\|fwallet_/', $token);
    }
}
