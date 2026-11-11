<?php

namespace App\Models;

use App\Enums\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    // A tabela só tem created_at: movimentações nunca são alteradas
    const UPDATED_AT = null;

    protected $fillable = ['wallet_id', 'type', 'amount', 'balance_after'];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
        ];
    }

    /** @return BelongsTo<Wallet, $this> */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
