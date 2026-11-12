<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Só os proxies internos (Docker e localhost) são confiáveis: um X-Forwarded-For
        // forjado pelo cliente não substitui o IP real usado nos limites de requisição
        $middleware->trustProxies(at: ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', '127.0.0.1']);

        // A API não tem página de login: sem token, o resultado é um 401 em JSON
        $middleware->redirectGuestsTo(fn (): ?string => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $ehApi = fn (Request $request): bool => $request->is('api/*') || $request->expectsJson();

        // Toda rota da API responde JSON, mesmo sem o cabeçalho Accept
        $exceptions->shouldRenderJsonWhen($ehApi);

        // Formato único de erro: { message, code }. A validação acrescenta o campo errors
        // (detalhe por campo), e as exceções de domínio têm o próprio render().
        $erro = function (string $mensagem, string $codigo, int $status, array $cabecalhos = []): JsonResponse {
            return response()->json(['message' => $mensagem, 'code' => $codigo], $status, $cabecalhos);
        };

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

        $exceptions->render(fn (ValidationException $e, Request $request) => $ehApi($request)
            ? response()->json([
                'message' => $e->getMessage(),
                'code' => 'validation_failed',
                'errors' => $e->errors(),
            ], $e->status)
            : null);

        // Qualquer outro erro sai no mesmo formato, sem detalhe interno. Com APP_DEBUG ligado,
        // o Laravel mostra o erro completo, o que ajuda no desenvolvimento
        $exceptions->render(function (Throwable $e, Request $request) use ($ehApi, $erro): ?JsonResponse {
            if (! $ehApi($request) || config('app.debug')) {
                return null;
            }

            if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
                return $erro('Não foi possível concluir a requisição.', 'request_error', $e->getStatusCode());
            }

            return $erro('Erro interno. Tente novamente em instantes.', 'server_error', 500);
        });
    })->create();
