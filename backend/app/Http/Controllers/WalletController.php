<?php

namespace App\Http\Controllers;

use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function show(Request $request, WalletService $servico): JsonResponse
    {
        return response()->json(['data' => $servico->resumo($request->user())]);
    }
}
