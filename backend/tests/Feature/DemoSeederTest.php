<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_cria_usuario_demo_com_saldo_consistente(): void
    {
        $this->seed(DemoSeeder::class);

        $usuario = User::where('email', DemoSeeder::EMAIL)->firstOrFail();
        $carteira = Wallet::where('user_id', $usuario->id)->firstOrFail();

        $creditos = (int) $carteira->transactions()->where('type', 'credit')->sum('amount');
        $debitos = (int) $carteira->transactions()->where('type', 'debit')->sum('amount');
        $ultima = $carteira->transactions()->latest()->latest('id')->firstOrFail();

        $this->assertEquals(10, $carteira->transactions()->count());
        $this->assertEquals(49610, $carteira->balance);
        $this->assertEquals($creditos - $debitos, $carteira->balance);
        $this->assertEquals($carteira->balance, $ultima->balance_after);
        $this->assertEquals(0, $carteira->transactions()->where('balance_after', '<', 0)->count());
    }

    public function test_seeder_pode_rodar_duas_vezes_sem_duplicar(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->assertEquals(1, User::where('email', DemoSeeder::EMAIL)->count());
        $this->assertEquals(1, Wallet::count());
        $this->assertEquals(10, Wallet::firstOrFail()->transactions()->count());
    }

    public function test_usuario_demo_consegue_entrar_pela_api(): void
    {
        $this->seed(DemoSeeder::class);

        $this->postJson('/api/auth/login', [
            'email' => DemoSeeder::EMAIL,
            'password' => DemoSeeder::SENHA,
        ])->assertOk()->assertJsonStructure(['token']);
    }
}
