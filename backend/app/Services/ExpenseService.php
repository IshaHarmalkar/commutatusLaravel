<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseItemSplit;
use App\Models\ExpenseParticipantSplit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function createExpense(array $data): Expense
    {
        return DB::transaction(function () use ($data) {
            $payerId = Auth::id();

            $expense = Expense::create([
                'paid_by_id' => $payerId,
                'name' => $data['name'],
                'amount' => $data['amount'],
                'tax' => $data['tax'] ?? 0,
                'tip' => $data['tip'] ?? 0,
            ]);

            $participantIds = collect($data['participant_ids'])->push($payerId)->unique();
            $expense->syncParticipants($participantIds->toArray());

           // $debtMap = $participantIds->fillKeys(0)->toArray();
            $debtMap = array_fill_keys($participantIds->toArray(), 0);

            $itemizedSplits = [];
            $count = $participantIds->count();


            //not adding the tax + tip / n inndividauls share to db, for now
            $extra = (float) ($data['tax'] ?? 0) + (float) ($data['tip'] ?? 0);
            if ($extra > 0) {
                $extraShare = $extra / $count;
                foreach ($debtMap as $userId => $currentTotal) {

                    $debtMap[$userId] += $extraShare;
                }
            }

            // building item splits
            foreach ($data['items'] as $itemData) {
                $item = $expense->items()->create([
                    'name' => $itemData['name'],
                    'amount' => $itemData['amount'],
                    'type' => $itemData['type'],
                    'assigned_to_id' => $itemData['assigned_to_id'] ?? null,
                ]);

                if ($itemData['type'] === 'assigned') {
                    $assignedId = $itemData['assigned_to_id'];
                    $amount = (float) $itemData['amount'];

                    $debtMap[$assignedId] += $amount;
                    $itemizedSplits[] = $this->formatSplit($item->id, $payerId, $assignedId, $amount);

                } else {
                    $itemShare = (float) $itemData['amount'] / $count;
                    foreach ($participantIds as $userId) {
                        $debtMap[$userId] += $itemShare;
                        $itemizedSplits[] = $this->formatSplit($item->id, $payerId, $userId, $itemShare);
                    }
                }
            }

            ExpenseItemSplit::insert($itemizedSplits);
            ExpenseParticipantSplit::insertTotals($expense->id, $payerId, $debtMap);

            return $expense;

        });
    }

    private function formatSplit($itemId, $creditorId, $debtorId, $amount): array
    {
        return [
            'expense_item_id' => $itemId,
            'creditor_id' => $creditorId,
            'debtor_id' => $debtorId,
            'amount' => round($amount, 2),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
