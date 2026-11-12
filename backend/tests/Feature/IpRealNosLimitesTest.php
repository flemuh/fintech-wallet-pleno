<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class IpRealNosLimitesTest extends TestCase
{
    use RefreshDatabase;

    // A requisição chega pelo container web (IP interno do Docker); o Nginx do host
    // já colocou o IP real do cliente no fim do X-Forwarded-For
    private const IP_DO_PROXY = '172.18.0.3';

    private const IP_REAL = '203.0.113.10';

    public function test_ip_falso_no_x_forwarded_for_nao_escapa_do_limite_de_login(): void
    {
        for ($tentativa = 1; $tentativa <= 5; $tentativa++) {
            $this->tentarLogin("10.0.0.{$tentativa}, ".self::IP_REAL)->assertUnprocessable();
        }

        $this->tentarLogin('10.0.0.99, '.self::IP_REAL)->assertTooManyRequests();
    }

    public function test_ip_publico_falso_tambem_nao_escapa_do_limite(): void
    {
        for ($tentativa = 1; $tentativa <= 5; $tentativa++) {
            $this->tentarLogin("198.51.100.{$tentativa}, ".self::IP_REAL)->assertUnprocessable();
        }

        $this->tentarLogin('198.51.100.99, '.self::IP_REAL)->assertTooManyRequests();
    }

    private function tentarLogin(string $cabecalho): TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => self::IP_DO_PROXY])
            ->withHeaders(['X-Forwarded-For' => $cabecalho])
            ->postJson('/api/auth/login', ['email' => 'x@x.com', 'password' => 'senha-errada']);
    }
}
