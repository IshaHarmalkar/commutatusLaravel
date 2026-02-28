<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = Auth::id();

        return [
            'id' => $this->id,
            'from' => [
                'id' => $this->debtor_id,
                'name' => $this->fromUser->name ?? 'Unknown User'],
            'to' => [
                'id' => $this->creditor_id,
                'name' => $this->toUser->name ?? 'Unknowen User'],
            'amount' => (float) $this->amount,
            'notes' => $this->notes,
            'direction' => $this->debtor_id === $userId ? 'sent' : 'received',
            'created_at' => $this->created_at->toDateTimeString(),
        ];

    }
}
