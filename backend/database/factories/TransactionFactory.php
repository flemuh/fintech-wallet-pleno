<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Transaction> */
class TransactionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'wallet_id' => Wallet::factory(),
            'type' => TransactionType::Credit,
            'amount' => 1000,
            'balance_after' => 1000,
        ];
    }
}
