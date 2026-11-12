<?php

namespace App\Http\Controllers;

use App\Http\Requests\WalletOperationRequest;
use App\Http\Resources\TransactionResource;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;

class DepositController extends Controller
{
    public function store(WalletOperationRequest $request, WalletService $servico): JsonResponse
    {
        /** @var User $usuario */
        $usuario = $request->user();
        $transacao = $servico->depositar($usuario, $request->valorEmCentavos(), $request->chaveDeIdempotencia());

        return TransactionResource::make($transacao)->response()->setStatusCode(201);
    }
}
