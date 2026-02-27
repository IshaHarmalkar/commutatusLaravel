<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\ExpenseItemSplit;
use App\Models\Participant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\ExpenseService;

class ExpenseController extends Controller
{
    public function store(StoreExpenseRequest $request, ExpenseService $expenseService): JsonResponse
    {
        try{
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
        } catch(\Throwable $e) {
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

        if(!$isMember){
            return response()->json(['message' => 'Access denied'], 403);
        }

        $expense->load([
            'paidBy',
            'participants.user',
            'items.assignedTo',
            'items.splits.creditor',
            'items.splits.debtor',
            'participantSplits'
        ]);

        $youOwe = $expense->participantSplits->where('debtor_id', $userId)->first()?->amount ?? 0;
        $owedToYou = $expense->participantSplits->where('creditor_id', $userId)->sum('amount');

        return response()->json([
            'data' => [
                'expense' => new ExpenseResource($expense),
                'your_summary' => [
                    'you_owe' => round((float) $youOwe, 2),
                    'owed_to_you' => round((float) $owedToYou, 2),
                    'net' => round((float) $owedToYou - (float) $youOwe, 2),
                ],
            ],
        ]);


    }
}
