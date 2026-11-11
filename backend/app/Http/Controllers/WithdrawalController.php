<?php

namespace App\Http\Controllers;

use App\Http\Requests\WalletOperationRequest;
use App\Http\Resources\TransactionResource;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;

class WithdrawalController extends Controller
{
    public function store(WalletOperationRequest $request, WalletService $servico): JsonResponse
    {
        $transacao = $servico->sacar($request->user(), $request->valorEmCentavos());

        return TransactionResource::make($transacao)->response()->setStatusCode(201);
    }
}
