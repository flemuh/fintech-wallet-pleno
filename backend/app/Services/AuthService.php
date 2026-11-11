<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class AuthService
{
    /**
     * Cria o usuário e a carteira com saldo zero na mesma transação
     * e devolve o usuário junto com um token de acesso.
     *
     * @param  array<string, mixed>  $dados
     * @return array{usuario: User, token: string}
     */
    public function registrar(array $dados): array
    {
        return DB::transaction(function () use ($dados) {
            $usuario = User::create($dados);
            $usuario->wallet()->create(['balance' => 0]);
            $token = $usuario->createToken('api')->plainTextToken;

            return ['usuario' => $usuario, 'token' => $token];
        });
    }
}
