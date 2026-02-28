<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Models\Participant;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function store(StoreExpenseRequest $request, ExpenseService $expenseService): JsonResponse
    {
        try {
            $expense = $expenseService->createExpense($request->validated());

            $expense->load([
                'paidBy',
                'participants.user',
                'items.splits.creditor',
                'items.splits.debtor',
                'participantSplits.debtor',
            ]);

            return response()->json([
                'message' => 'Expense created successfully',
                'data' => new ExpenseResource($expense),
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to create expense',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {

        // get expenses where the user is creditor or a participant.
        // expenses where the user was involved
        $expenses = Expense::query()
            ->where('paid_by_id', Auth::id())
            ->orWhereHas('participants', function ($query) {
                $query->where('user_id', Auth::id());
            })->with([
                'paidBy',
                'participants.user',
                'items.splits.creditor',
                'items.splits.debtor',
            ])->latest()->paginate(15);

        return response()->json([
            'data' => ExpenseResource::collection($expenses->items()),
            'meta' => [
                'current_page' => $expenses->currentPage(),
                'last_page' => $expenses->lastPage(),
                'per_page' => $expenses->perPage(),
                'total' => $expenses->total(),
            ],
        ]);

    }

    public function show(Expense $expense): JsonResponse
    {
        $userId = Auth::id();
        $isMember = $expense->paid_by_id === $userId || $expense->participants()->where('user_id', $userId)->exists();

        if (! $isMember) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $expense->load([
            'paidBy',
            'participants.user',
            'items.splits.debtor',
        ]);

        $taxTipTotal = (float) $expense->tax + (float) $expense->tip;
        $count = $expense->participants->count();
        $taxTipShare = $count > 0 ? round($taxTipTotal / $count, 2) : 0;

        $taxTipShares = $expense->participants->mapWithKeys(fn ($p) => [
            $p->user->name => $taxTipShare,
        ]);

        return response()->json([
            'data' => [
                'expense' => new ExpenseResource($expense),
                'tax_tip_breakdown' => $taxTipShares,
                'your_summary' => $this->calculateSummary($expense, $userId, $taxTipShare),
            ],
        ]);

    }

    private function calculateSummary(Expense $expense, int $userId, float $myTaxTipShare): array
    {
        // 1. Get all individual item splits for this user
        $allSplits = $expense->items->flatMap->splits;

        // 2. Amount you owe to the person who paid (the Creditor)
        // We exclude splits where you are the creditor (i.e., you paid for yourself)
        $youOweItems = $allSplits
            ->where('debtor_id', $userId)
            ->where('creditor_id', '!=', $userId)
            ->sum('amount');

        // 3. Amount others owe to you (only if YOU were the one who paid the bill)
        $owedToYouItems = $allSplits
            ->where('creditor_id', $userId)
            ->where('debtor_id', '!=', $userId)
            ->sum('amount');

        // 4. Add the Tax/Tip share
        // If you are NOT the payer, you owe your tax share to the payer.
        // If you ARE the payer, others owe their tax shares to you.
        $finalYouOwe = (float) $youOweItems;
        $finalOwedToYou = (float) $owedToYouItems;

        if ($expense->paid_by_id !== $userId) {
            $finalYouOwe += $myTaxTipShare;
        } else {
            // If you paid, the "Tax & Tip" owed to you is the total minus your own share
            $othersTaxTip = ((float) $expense->tax + (float) $expense->tip) - $myTaxTipShare;
            $finalOwedToYou += $othersTaxTip;
        }

        return [
            'you_owe' => round($finalYouOwe, 2),
            'owed_to_you' => round($finalOwedToYou, 2),
            'net' => round($finalOwedToYou - $finalYouOwe, 2),
        ];
    }
}
