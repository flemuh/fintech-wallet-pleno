<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepositController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::post('/auth/register', [AuthController::class, 'registrar']);
Route::post('/auth/login', [AuthController::class, 'entrar'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'sair']);

    Route::post('/wallet/deposits', [DepositController::class, 'store']);
});
