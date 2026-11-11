<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->definirLimitesDeRequisicao();
    }

    /** Limites nomeados, usados nas rotas com o middleware throttle:<nome>. */
    private function definirLimitesDeRequisicao(): void
    {
        // Criação de contas: por IP, contra cadastro em massa
        RateLimiter::for('registro', fn (Request $request) => Limit::perMinute(5)
            ->by($this->ipDe($request)));

        // Login: por IP e e-mail, contra tentativa de adivinhar senha
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by($this->ipDe($request).'|'.$request->string('email')->lower()->toString()));

        // Depósito e saque: por usuário
        RateLimiter::for('operacoes', fn (Request $request) => Limit::perMinute(20)
            ->by($this->usuarioDe($request)));

        // Demais rotas autenticadas (histórico, resumo, logout): por usuário
        RateLimiter::for('carteira', fn (Request $request) => Limit::perMinute(60)
            ->by($this->usuarioDe($request)));
    }

    private function ipDe(Request $request): string
    {
        return $request->ip() ?? 'desconhecido';
    }

    private function usuarioDe(Request $request): string
    {
        return (string) ($request->user()?->getAuthIdentifier() ?? $this->ipDe($request));
    }
}
