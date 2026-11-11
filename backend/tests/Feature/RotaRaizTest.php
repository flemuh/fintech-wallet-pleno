<?php

namespace Tests\Feature;

use Tests\TestCase;

class RotaRaizTest extends TestCase
{
    // Só existe no desenvolvimento: em produção o Nginx serve o Vue em "/" e repassa apenas /api
    public function test_raiz_do_backend_responde_o_nome_da_aplicacao(): void
    {
        $this->getJson('/')->assertOk()->assertExactJson(['name' => 'Fintech Wallet']);
    }
}
