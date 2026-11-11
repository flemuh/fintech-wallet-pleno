<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

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
}
