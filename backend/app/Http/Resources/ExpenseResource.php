<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'amount' => $this->amount,
            'tax' => $this->tip,
            'paid_by' => [
                'id' => $this->paidBy->id,
                'name' => $this->paidBy->name,
            ],
            'participants' => $this->participants->map(fn ($p) => [
                'id' => $p->user->id,
                'name' => $p->user->name,
            ]),

            'items' => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'amount' => $item->amount,
                'type' => $item->type,
                'assigned_to' => $item->assignedTo ? [
                    'id' => $item->assignedTo->name,
                ] : null,
                'splits' => $this->splits->map(fn ($split) => [
                    'id' => $split->id,
                    'creditor' => ['id' => $split->creditor->id, 'name' => $split->creditor->name],
                    'debtor' => ['id' => $split->debtor->id, 'name' => $split->debtor->name],
                    'amount' => $split->amount,
                ]),
            ]),
            'created_at' => $this->created_at->toDateTimeString(),

        ];
    }
}
