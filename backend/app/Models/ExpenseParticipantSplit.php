<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseParticipantSplit extends Model
{
    protected $fillable = [
        'expense_id',
        'creditor_id',
        'debtor_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function creditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creditor_id');
    }

    public function debtor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'debtor_id');
    }
}
