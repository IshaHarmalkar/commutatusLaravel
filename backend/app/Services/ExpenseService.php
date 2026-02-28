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

            // not adding the tax + tip / n inndividauls share to db, for now
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

    public function getExpenseSummary(Expense $expense, int $userId): array
    {

        $expense->load(['participants.user', 'items.splits']);

        $taxTipTotal = (float) $expense->tax + (float) $expense->tip;
        $count = $expense->participants->count();
        $taxTipShare = $count > 0 ? round($taxTipTotal / $count, 2) : 0;

        $taxTipBreakdown = $expense->participants->mapWithKeys(fn ($p) => [
            $p->user->name => $taxTipShare,
        ]);

        $allSplits = $expense->items->flatMap->splits;

        $userOweItems = $allSplits
            ->where('debtor_id', $userId)
            ->where('creditor_id', '!=', $userId)
            ->sum('amount');

        $owedToUserItems = $allSplits
            ->where('creditor_id', $userId)
            ->where('debtor_id', '!=', $userId)
            ->sum('amount');

        $finalUserOwe = (float) $userOweItems;
        $finalOwedToUser = (float) $owedToUserItems;

        if ($expense->paid_by_id !== $userId) {
            $finalUserOwe += $taxTipShare;
        } else {
            $othersTaxTip = $taxTipTotal - $taxTipShare;
            $finalOwedToUser += $othersTaxTip;
        }

        return [
            'tax_tip_breakdown' => $taxTipBreakdown,
            'your_summary' => [
                'you_owe' => round($finalUserOwe, 2),
                'owed_to_you' => round($finalOwedToUser, 2),
                'net' => round($finalOwedToUser - $finalUserOwe, 2),
            ],
        ];

    }
}
