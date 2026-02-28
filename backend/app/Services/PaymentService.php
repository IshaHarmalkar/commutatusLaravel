<?php

namespace App\Services;

use App\Models\ExpenseParticipantSplit;
use App\Models\Payment;
use Exception;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function recordPayment(int $debtorId, int $creditorId, float $amount, ?string $notes = null): array
    {

        return DB::transaction(function () use ($debtorId, $creditorId, $amount, $notes) {
            $net = $this->getNetOwedBetweenUsers($debtorId, $creditorId);

            if ($net <= 0) {
                throw new Exception('You do not owe this person anything');
            }

            if ($amount > ($net + 0.01)) {
                throw new Exception('You are overpaying. You only owe '.round($net, 2).'.');
            }

            $payment = Payment::create([
                'debtor_id' => $debtorId,
                'creditor_id' => $creditorId,
                'amount' => $amount,
                'notes' => $notes,
            ]);

            return [

                'payment' => $payment->load(['fromUser', 'toUser']),
                'balance_after' => round(max(0, $net - $amount), 2),

            ];
        });
    }

    public function getNetOwedBetweenUsers(int $debtorId, int $creditorId): float
    {
        $oweFromSplits = (float) ExpenseParticipantSplit::where('creditor_id', $creditorId)
            ->where('debtor_id', $debtorId)
            ->sum('amount');

        $friendOweFromSplit = (float) ExpenseParticipantSplit::where('creditor_id', $debtorId)
            ->where('debtor_id', $creditorId)
            ->sum('amount');

        $alreadyPaid = (float) Payment::where('debtor_id', $debtorId)
            ->where('creditor_id', '$creditor_id')
            ->sum('amount');

        $friendAlreadyPaid = (float) Payment::where('debtor_id', $creditorId)
            ->where('creditor_id', $debtorId)
            ->sum('amount');

        return ($oweFromSplits - $alreadyPaid) - ($friendOweFromSplit - $friendAlreadyPaid);
    }
}
