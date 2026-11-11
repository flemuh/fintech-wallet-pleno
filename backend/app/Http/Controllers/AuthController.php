<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $servico) {}

    public function registrar(RegisterRequest $request): JsonResponse
    {
        $resultado = $this->servico->registrar($request->validated());

        return response()->json([
            'user' => new UserResource($resultado['usuario']),
            'token' => $resultado['token'],
        ], 201);
    }

    public function entrar(LoginRequest $request): JsonResponse
    {
        $resultado = $this->servico->entrar(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        return response()->json([
            'user' => new UserResource($resultado['usuario']),
            'token' => $resultado['token'],
        ]);
    }

    public function sair(Request $request): Response
    {
        /** @var User $usuario */
        $usuario = $request->user();
        $this->servico->sair($usuario);

        return response()->noContent();
    }
}
