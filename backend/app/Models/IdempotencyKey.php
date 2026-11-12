<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdempotencyKey extends Model
{
    // Só created_at: a chave nunca é alterada
    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'key', 'request_hash', 'transaction_id'];

    /** @return BelongsTo<Transaction, $this> */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
