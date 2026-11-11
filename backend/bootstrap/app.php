<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        // A API não tem página de login: sem token, o resultado é um 401 em JSON
        $middleware->redirectGuestsTo(fn (): ?string => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $ehApi = fn (Request $request): bool => $request->is('api/*') || $request->expectsJson();

        // Toda rota da API responde JSON, mesmo sem o cabeçalho Accept
        $exceptions->shouldRenderJsonWhen($ehApi);

        // Formato único de erro: { message, code }. A validação (422) mantém o padrão
        // do Laravel (message + errors), e as exceções de domínio têm o próprio render().
        $erro = fn (string $mensagem, string $codigo, int $status, array $cabecalhos = []): JsonResponse => response()->json(['message' => $mensagem, 'code' => $codigo], $status, $cabecalhos);

        $exceptions->render(fn (AuthenticationException $e, Request $request) => $ehApi($request)
            ? $erro('Não autenticado.', 'unauthenticated', 401)
            : null);

        $exceptions->render(fn (NotFoundHttpException $e, Request $request) => $ehApi($request)
            ? $erro('Recurso não encontrado.', 'not_found', 404)
            : null);

        $exceptions->render(fn (MethodNotAllowedHttpException $e, Request $request) => $ehApi($request)
            ? $erro('Método não permitido.', 'method_not_allowed', 405)
            : null);

        // Mantém os cabeçalhos do limitador (Retry-After e X-RateLimit-*)
        $exceptions->render(fn (TooManyRequestsHttpException $e, Request $request) => $ehApi($request)
            ? $erro('Muitas requisições. Tente novamente em instantes.', 'too_many_requests', 429, $e->getHeaders())
            : null);
    })->create();
