<?php

namespace Tests\Feature;

use Tests\TestCase;

class PaginaUpTest extends TestCase
{
    public function test_pagina_up_do_laravel_nao_existe(): void
    {
        $this->get('/up')->assertNotFound();
    }

    public function test_health_check_da_api_continua_respondendo(): void
    {
        $this->getJson('/api/health')->assertOk()->assertExactJson(['status' => 'ok']);
    }
}
