<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionIndexRequest;
use App\Http\Resources\TransactionResource;
use App\Services\WalletService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends Controller
{
    public function index(TransactionIndexRequest $request, WalletService $servico): AnonymousResourceCollection
    {
        $transacoes = $servico->historico($request->user(), $request->validated());

        return TransactionResource::collection($transacoes);
    }
}
