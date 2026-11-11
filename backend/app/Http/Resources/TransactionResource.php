<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Transaction */
class TransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,                   // enum vira "credit"/"debit" no JSON
            'amount' => $this->amount,               // em centavos
            'balance_after' => $this->balance_after, // em centavos
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
