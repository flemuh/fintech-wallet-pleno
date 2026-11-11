<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WithdrawalController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::post('/auth/register', [AuthController::class, 'registrar'])->middleware('throttle:registro');
Route::post('/auth/login', [AuthController::class, 'entrar'])->middleware('throttle:login');

Route::middleware(['auth:sanctum', 'throttle:carteira'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'sair']);

    Route::get('/wallet/transactions', [TransactionController::class, 'index']);

    Route::get('/wallet', [WalletController::class, 'show']);

    // Depósito e saque têm um limite próprio, mais baixo
    Route::middleware('throttle:operacoes')->group(function () {
        Route::post('/wallet/deposits', [DepositController::class, 'store']);

        Route::post('/wallet/withdrawals', [WithdrawalController::class, 'store']);
    });
});
