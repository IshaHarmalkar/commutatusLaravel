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
            'tax' => $this->tax,
            'tip' => $this->tip,
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
                'total_amount' => (float) $item->amount,
                'type' => $item->type,
                'shares' => $item->splits->mapWithKeys(fn ($split) => [
                    $split->debtor->name => (float) $split->amount,
                ]),
            ]),
            'created_at' => $this->created_at->toDateTimeString(),

        ];
    }
}
