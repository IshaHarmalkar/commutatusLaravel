<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseItemSplit;
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
        $me = Auth::id();

        // per friend -> how much friend owes
        $creditorSplits = ExpenseItemSplit::where('creditor_id', $me)
            ->selectRaw('debtor_id as friend_id, SUM(amount) as total')
            ->groupBy('debtor_id')
            ->pluck('total', 'friend_id');

        $debtorSplits = ExpenseItemSplit::where('debtor_id', $me)
            ->selectRaw('creditor_id as friend_id, SUM(amount) as total')
            ->groupBy('creditor_id')
            ->pluck('total', 'friend_id');

        // payments made
        $sentPayments = Payment::where('debtor_id', $me)
            ->selectRaw('creditor_id as friend_id, SUM(amount) as total')
            ->groupBy('creditor_id')
            ->pluck('total', 'friend_id');

        $receivedPayments = Payment::where('creditor_id', $me)
            ->selectRaw('debtor_id as friend_id, SUM(amount) as total')
            ->groupBy('debtor_id')
            ->pluck('total', 'friend_id');

        // unique friends across queries
        $friendIds = collect()->merge($creditorSplits->keys())->merge($debtorSplits->keys())->unique()->values();

        $friends = User::whereIn('id', $friendIds)
            ->get()->keyBy('id');

        // net per friend
        $owedTOYouList = [];
        $youOweList = [];

        foreach ($friendIds as $friendId) {
            $theyOweMe = (float) ($creditorSplits[$friendId] ?? 0);
            $iOweThem = (float) ($debtorSplits[$friendId] ?? 0);
            $iHavePaid = (float) ($sentPayments[$friendId] ?? 0);
            $theyHavePaid = (float) ($receivedPayments[$friendId] ?? 0);

            // net > 0 means they owe me, net < 0 means I owe them
            $net = ($theyOweMe - $theyHavePaid) - ($iOweThem - $iHavePaid);
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
                $owedToYouList[] = $friendData; // they owe you
            } else {
                $youOweList[] = $friendData;    // you owe them
            }
        }

        $totalOwedToYou = round(collect($owedToYouList)->sum('amount'), 2);
        $totalYouOwe = round(collect($youOweList)->sum('amount'), 2);
        $totalBalance = round($totalOwedToYou - $totalYouOwe, 2);

        return response()->json([
            'data' => [
                'total_balance' => $totalBalance,
                'total_owed_to_you' => $totalOwedToYou,
                'total_you_owe' => $totalYouOwe,
                'owed_to_you' => $owedToYouList,
                'you_owe' => $youOweList,
            ],
        ]);
    }
}
