<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

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

    /**
     * Confere as credenciais e devolve o usuário com um novo token.
     * A mensagem é a mesma para e-mail inexistente e senha errada,
     * para não revelar quais e-mails estão cadastrados.
     *
     * @return array{usuario: User, token: string}
     */
    public function entrar(string $email, string $senha): array
    {
        $usuario = User::where('email', $email)->first();

        if (! $usuario || ! Hash::check($senha, $usuario->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        return [
            'usuario' => $usuario,
            'token' => $usuario->createToken('api')->plainTextToken,
        ];
    }

    /** Revoga apenas o token usado nesta requisição. */
    public function sair(User $usuario): void
    {
        /** @var PersonalAccessToken $token */
        $token = $usuario->currentAccessToken();
        $token->delete();
    }
}
