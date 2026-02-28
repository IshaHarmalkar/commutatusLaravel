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

        $expenses = Expense::involvingUser(Auth::id())
            ->with(['paidBy',
                'participants.user',
                'items.splits.creditor',
                'items.splits.debtor', ])
            ->latest()->paginate(15);

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

    public function show(Expense $expense, ExpenseService $expenseService): JsonResponse
    {
        $userId = Auth::id();
        $isMember = Expense::involvingUser($userId)
            ->where('id', $expense->id)
            ->exists();

        if (! $isMember) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $summaryData = $expenseService->getExpenseSummary($expense, $userId);

        return response()->json([
            'data' => array_merge([
                'expense' => new ExpenseResource($expense),

            ], $summaryData),
        ]);

    }
}
