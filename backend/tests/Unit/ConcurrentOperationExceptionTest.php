<?php

namespace Tests\Unit;

use App\Exceptions\ConcurrentOperationException;
use Tests\TestCase;

class ConcurrentOperationExceptionTest extends TestCase
{
    public function test_operacao_concorrente_responde_409_com_codigo_e_mensagem(): void
    {
        $resposta = (new ConcurrentOperationException)->render();

        $this->assertSame(409, $resposta->getStatusCode());
        $this->assertSame([
            'message' => 'Operação concorrente, tente novamente.',
            'code' => 'concurrent_operation',
        ], $resposta->getData(true));
    }
}
