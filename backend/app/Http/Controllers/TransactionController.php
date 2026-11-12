<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionIndexRequest;
use App\Http\Resources\TransactionResource;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends Controller
{
    public function index(TransactionIndexRequest $request, WalletService $servico): AnonymousResourceCollection
    {
        /** @var User $usuario */
        $usuario = $request->user();
        $transacoes = $servico->historico($usuario, $request->validated());

        return TransactionResource::collection($transacoes);
    }
}
