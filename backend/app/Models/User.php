<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    //expenses paid by user -> user is creditor here
    public function paidExpenses(): HasMany{
        return $this->hasMany(Participant::class, 'user_id');       
    }

    public function participatedExpenses(): HasMany {
        return $this->hasMany(Participant::class, 'user_id');
    }

    public function creditorSplits():HasMany{
        return $this->hasMany(ExpennseItemSplit::class, 'creditor_id');
    }

    //payments paid
    public function sentPayments(): HasMany{
        return $this->hasMany(Payment::class, 'debtor_id');
    }

    //payments received
    public function receivedPayments(): HasMany{
        return $this->hasMany(Payment::class, 'creditor_id');
    }

    //item amount
    public function assignedItems(): HasMany{
        return $this->hasMany(ExpenseItem::class, 'assigned_to_id');
    }


}
