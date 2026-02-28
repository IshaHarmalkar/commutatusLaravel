<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Expense extends Model
{
    protected $fillable = [
        'paid_by_id',
        'name',
        'amount',
        'tax',
        'tip',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'tip' => 'decimal:2',
    ];

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by_id');  // creditor_id
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExpenseItem::class);
    }

    public function splits(): HasManyThrough
    {
        return $this->hasManyThrough(
            ExpenseItemSplit::class,
            ExpenseItem::class
        );
    }

    public function participantSplits()
    {
        return $this->hasMany(ExpenseParticipantSplit::class);
    }

    // create the participant list for the expense
    public function syncParticipants(array $ids): void
    {
        $formatted = collect($ids)->map(fn ($id) => ['user_id' => $id])->toArray();
        $this->participants()->createMany($formatted);
    }

    // scopes

    // get expenses where user is involved
    public function scopeInvolvingUser($query, $userId)
    {
        return $query->where('paid_by_id', $userId)
            ->orWhereHas('participants', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
    }

    public function scopeSharedBetween($query, $userA, $userB)
    {
        return $query->where(fn ($q) => $q->forUser($userA))
            ->where(fn ($q) => $q->forUser($userB));
    }
}
