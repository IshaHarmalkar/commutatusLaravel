<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseItem extends Model
{
    protected $fillable = [ 
        'expense_id',
        'name',
        'amount',
        'type',
        'assigned_to_id',
    ];

    protected $casts = ['amount' => 'decimal:2',];

    public function expense(): BelongsTo{
        return $this->belongsTo(Expense::class);
    }

    public function assignedTo(): BelongsTo{
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function splits(): HasMany{
        return $this->hasMany(ExpenseItemSplit::class);
    }
}
