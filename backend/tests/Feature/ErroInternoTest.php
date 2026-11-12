<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ErroInternoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Em produção o APP_DEBUG fica desligado: é esse comportamento que importa
        config(['app.debug' => false]);
    }

    public function test_erro_inesperado_responde_500_no_formato_padrao_sem_detalhe(): void
    {
        Route::get('/api/teste-falha', fn () => throw new RuntimeException('SQLSTATE detalhe interno'));

        $this->getJson('/api/teste-falha')
            ->assertStatus(500)
            ->assertExactJson(['message' => 'Erro interno. Tente novamente em instantes.', 'code' => 'server_error']);
    }

    public function test_outro_erro_http_mantem_o_status_e_o_formato_padrao(): void
    {
        Route::get('/api/teste-proibido', fn () => abort(403));

        $this->getJson('/api/teste-proibido')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Não foi possível concluir a requisição.', 'code' => 'request_error']);
    }
}
