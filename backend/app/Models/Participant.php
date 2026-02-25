<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Participant extends Model
{
    protected $fillable = [
        'expense_id',
        'user_id',
    ];

    public function expense(): BelongsTo
    {
        return $this->belongs(Expense::class);
    }

    public function User(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
