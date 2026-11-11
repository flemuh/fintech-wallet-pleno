<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorsTest extends TestCase
{
    public function test_origem_permitida_recebe_o_cabecalho_cors(): void
    {
        config(['cors.allowed_origins' => ['https://wallet.speedlora.app']]);

        $this->withHeaders(['Origin' => 'https://wallet.speedlora.app'])
            ->getJson('/api/health')
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'https://wallet.speedlora.app');
    }

    public function test_origem_desconhecida_nao_e_liberada(): void
    {
        config(['cors.allowed_origins' => ['https://wallet.speedlora.app']]);

        $resposta = $this->withHeaders(['Origin' => 'https://site-malicioso.example'])
            ->getJson('/api/health');

        // Com uma única origem configurada o cabeçalho devolve sempre essa origem;
        // o navegador bloqueia porque ela não é igual à origem que fez a chamada.
        $this->assertNotSame(
            'https://site-malicioso.example',
            $resposta->headers->get('Access-Control-Allow-Origin'),
        );
        $this->assertNotSame('*', $resposta->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_sem_origens_configuradas_nenhuma_origem_externa_e_liberada(): void
    {
        config(['cors.allowed_origins' => []]);

        $this->withHeaders(['Origin' => 'https://wallet.speedlora.app'])
            ->getJson('/api/health')
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }
}
