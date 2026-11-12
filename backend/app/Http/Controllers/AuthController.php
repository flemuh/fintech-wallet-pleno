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
    public function registrar(RegisterRequest $request, AuthService $servico): JsonResponse
    {
        $resultado = $servico->registrar($request->validated());

        return response()->json([
            'user' => new UserResource($resultado['usuario']),
            'token' => $resultado['token'],
        ], 201);
    }

    public function entrar(LoginRequest $request, AuthService $servico): JsonResponse
    {
        $resultado = $servico->entrar(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        return response()->json([
            'user' => new UserResource($resultado['usuario']),
            'token' => $resultado['token'],
        ]);
    }

    public function sair(Request $request, AuthService $servico): Response
    {
        /** @var User $usuario */
        $usuario = $request->user();
        $servico->sair($usuario);

        return response()->noContent();
    }
}
