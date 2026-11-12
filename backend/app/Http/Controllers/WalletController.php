<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function show(Request $request, WalletService $servico): JsonResponse
    {
        /** @var User $usuario */
        $usuario = $request->user();

        return response()->json(['data' => $servico->resumo($usuario)]);
    }
}
