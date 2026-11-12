<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CadastroSeguroTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, string>  $troca
     * @return array<string, string>
     */
    private function dados(array $troca = []): array
    {
        return [...['name' => 'Ana Souza', 'email' => 'ana@exemplo.com', 'password' => 'Senha@1234'], ...$troca];
    }

    public function test_senha_com_todos_os_criterios_e_aceita(): void
    {
        $this->postJson('/api/auth/register', $this->dados())->assertCreated();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function senhasFracas(): array
    {
        return [
            'sem maiúscula' => ['senha@1234', 'O campo senha deve conter letras maiúsculas e minúsculas.'],
            'sem minúscula' => ['SENHA@1234', 'O campo senha deve conter letras maiúsculas e minúsculas.'],
            'sem número' => ['Senha@abcd', 'O campo senha deve conter pelo menos um número.'],
            'sem símbolo' => ['Senha12345', 'O campo senha deve conter pelo menos um símbolo.'],
        ];
    }

    #[DataProvider('senhasFracas')]
    public function test_senha_sem_algum_criterio_retorna_422(string $senha, string $mensagem): void
    {
        $this->postJson('/api/auth/register', $this->dados(['password' => $senha]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.password', fn (array $erros): bool => in_array($mensagem, $erros, true));
    }

    public function test_senha_curta_retorna_422(): void
    {
        $this->postJson('/api/auth/register', $this->dados(['password' => 'Ab@1']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_senha_com_64_caracteres_e_aceita(): void
    {
        $this->postJson('/api/auth/register', $this->dados(['password' => 'Senha@12'.str_repeat('x', 56)]))
            ->assertCreated();
    }

    public function test_senha_acima_de_64_caracteres_retorna_422(): void
    {
        $this->postJson('/api/auth/register', $this->dados(['password' => 'Senha@12'.str_repeat('x', 57)]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.password.0', 'O campo senha não pode ter mais de 64 caracteres.');
    }

    public function test_senha_acima_de_72_bytes_retorna_422(): void
    {
        // 47 caracteres, mas 73 bytes: cada "ç" e "ã" ocupa 2 bytes
        $senha = 'Senha@12'.str_repeat('ção', 13);
        $mensagem = 'O campo senha ficou longo demais. Use menos acentos ou caracteres especiais.';

        $this->postJson('/api/auth/register', $this->dados(['password' => $senha]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.password.0', $mensagem);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function emailsInvalidos(): array
    {
        return [
            'sem usuário' => ['@emailqualquer'],
            'sem domínio' => ['ana@'],
            'domínio sem ponto' => ['ana@exemplo'],
            'com espaço' => ['ana souza@exemplo.com'],
            'arroba duplo' => ['ana@@exemplo.com'],
        ];
    }

    #[DataProvider('emailsInvalidos')]
    public function test_cadastro_com_email_invalido_retorna_422(string $email): void
    {
        $this->postJson('/api/auth/register', $this->dados(['email' => $email]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    #[DataProvider('emailsInvalidos')]
    public function test_login_com_email_invalido_retorna_422(string $email): void
    {
        $this->postJson('/api/auth/login', ['email' => $email, 'password' => 'Senha@1234'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }
}
