<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseParticipantSplit;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BalanceController extends Controller
{
    public function index(): JsonResponse
    {
        Log::info('BalanceController@index was hit by User: '.Auth::id());
        $user = Auth::id();

        $creditorSplits = ExpenseParticipantSplit::summarizeByFriend('creditor', $user)
            ->pluck('total', 'friend_id');

        $debtorSplits = ExpenseParticipantSplit::summarizeByFriend('debtor', $user)
            ->pluck('total', 'friend_id');

        // payments made
        $sentPayments = Payment::where('debtor_id', $user)
            ->selectRaw('creditor_id as friend_id, SUM(amount) as total')
            ->groupBy('creditor_id')
            ->pluck('total', 'friend_id');

        $receivedPayments = Payment::where('creditor_id', $user)
            ->selectRaw('debtor_id as friend_id, SUM(amount) as total')
            ->groupBy('debtor_id')
            ->pluck('total', 'friend_id');

        // unique friends across queries
        $friendIds = collect()->merge($creditorSplits->keys())
            ->merge($debtorSplits->keys())
            ->merge($sentPayments->keys())
            ->merge($receivedPayments->keys())
            ->unique()->values();

        $friends = User::whereIn('id', $friendIds)
            ->get()->keyBy('id');

        // net per friend
        $owedToUserList = [];
        $userOweList = [];

        foreach ($friendIds as $friendId) {
            $friendOwesUser = (float) ($creditorSplits[$friendId] ?? 0);
            $userOwesFriend = (float) ($debtorSplits[$friendId] ?? 0);

            // payments
            $userPaidFriend = (float) ($sentPayments[$friendId] ?? 0);
            $friendPaidUser = (float) ($receivedPayments[$friendId] ?? 0);

            // net > 0 means they owe me, net < 0 means I owe them
            $net = ($friendOwesUser - $friendPaidUser) - ($userOwesFriend - $userPaidFriend);
            $net = round($net, 2);

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

        $totalOwedToUser = round(collect($owedToUserList)->sum('amount'), 2);
        $totalUserOwe = round(collect($userOweList)->sum('amount'), 2);
        $totalBalance = round($totalOwedToUser - $totalUserOwe, 2);

        return response()->json([
            'data' => [
                'total_balance' => $totalBalance,
                'total_owed_to_user' => $totalOwedToUser,
                'total_user_owes' => $totalUserOwe,
                'owed_to_user' => $owedToUserList,
                'user_owes' => $userOweList,
            ],
        ]);
    }
}
