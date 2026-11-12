<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    /** O bcrypt só considera os primeiros 72 bytes da senha; acima disso o resto seria ignorado. */
    public const LIMITE_DE_BYTES_DA_SENHA = 72;

    /** Hash usado quando o e-mail não existe, para o login levar o mesmo tempo nos dois casos. */
    private static ?string $hashDeComparacao = null;

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

        // Sempre confere um hash: sem isso, a resposta mais rápida revelaria que o e-mail não existe
        self::$hashDeComparacao ??= Hash::make(Str::random(40));
        $hash = $usuario->password ?? self::$hashDeComparacao;
        $senhaConfere = strlen($senha) <= self::LIMITE_DE_BYTES_DA_SENHA && Hash::check($senha, $hash);

        if (! $usuario || ! $senhaConfere) {
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
