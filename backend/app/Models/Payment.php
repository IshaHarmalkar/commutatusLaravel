<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'debtor_id',
        'creditor_id',
        'amount',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // the one making the payment
    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'debtor_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creditor_id');
    }
}
