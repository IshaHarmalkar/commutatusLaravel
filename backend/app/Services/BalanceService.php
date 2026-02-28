<?php

namespace App\Services;

use App\Models\ExpenseParticipantSplit;
use App\Models\Payment;
use App\Models\User;

class BalanceService
{
    public function getBalanceSummary(int $userId): array
    {

        $creditorSplits = ExpenseParticipantSplit::summarizeByFriend('creditor', $userId)
            ->pluck('total', 'friend_id');

        $debtorSplits = ExpenseParticipantSplit::summarizeByFriend('debtor', $userId)
            ->pluck('total', 'friend_id');

        // payments made
        $sentPayments = Payment::where('debtor_id', $userId)
            ->selectRaw('creditor_id as friend_id, SUM(amount) as total')
            ->groupBy('creditor_id')
            ->pluck('total', 'friend_id');

        $receivedPayments = Payment::where('creditor_id', $userId)
            ->selectRaw('debtor_id as friend_id, SUM(amount) as total')
            ->groupBy('debtor_id')
            ->pluck('total', 'friend_id');

        // unique friends across queries
        $friendIds = collect([
            $creditorSplits->keys(),
            $debtorSplits->keys(),
            $sentPayments->keys(),
            $receivedPayments->keys(),
        ])->flatten()->unique()->values();

        $friends = User::whereIn('id', $friendIds)
            ->select('id', 'name', 'email')
            ->get()->keyBy('id');

        // net per friend
        $owedToUserList = [];
        $userOweList = [];

        foreach ($friendIds as $friendId) {
            if (! $friends->has($friendId)) {
                continue;
            }
            $net = $this->calculateNet($friendId, $creditorSplits, $debtorSplits, $sentPayments, $receivedPayments);

            // net > 0 means they owe me, net < 0 means I owe them

            // payments settled
            if ($net === 0.0) {
                continue;
            }

            $friendData = [
                'user' => [
                    'id' => $friendId,
                    'name' => $friends[$friendId]->name ?? 'Unknown',
                ],
                'amount' => abs($net),
            ];

            if ($net > 0) {
                $owedToUserList[] = $friendData; // friend owes user
            } else {
                $userOweList[] = $friendData;    // user owes friend
            }
        }

        return $this->formatFinalResponse($owedToUserList, $userOweList);

    }

    private function calculateNet($friendId, $cSplits, $dSplits, $sent, $received): float
    {
        $friendOwesUser = (float) ($cSplits[$friendId] ?? 0);
        $userOwesFriend = (float) ($dSplits[$friendId] ?? 0);
        $userPaidFriend = (float) ($sent[$friendId] ?? 0);
        $friendPaidUser = (float) ($received[$friendId] ?? 0);

        return round(($friendOwesUser - $friendPaidUser) - ($userOwesFriend - $userPaidFriend), 2);
    }

    private function formatFinalResponse(array $owedToUser, array $userOwes): array
    {
        $totalOwedToUser = round(collect($owedToUser)->sum('amount'), 2);
        $totalUserOwe = round(collect($userOwes)->sum('amount'), 2);

        return [
            'total_balance' => round($totalOwedToUser - $totalUserOwe, 2),
            'total_owed_to_user' => $totalOwedToUser,
            'total_user_owes' => $totalUserOwe,
            'owed_to_user' => $owedToUser,
            'user_owes' => $userOwes,
        ];
    }
}
