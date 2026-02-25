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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


class ExpenseController extends Controller
{
    public function store(StoreExpenseRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            // create expense record
            $expense = Expense::create([
                'paid_by_id' => Auth::id(),
                'name' => $request->name,
                'amount' => $request->amount,
                'tax' => $request->input('tax', 0),
                'tip' => $request->input('tip', 0),
            ]);

            // add participants->including creditor
            $participantIds = collect($request->participant_ids)->push(Auth::id())->unique()->values()->toArray();

            // add participants to db
            foreach ($participantIds as $userId) {
                Participant::create([
                    'expense_id' => $expense->id,
                    'user_id' => $userId,
                ]);
            }

            $participantCount = count($participantIds);

            // for each item in expense
            foreach ($request->items as $itemData) {
                $item = ExpenseItem::create([
                    'expense_id' => $expense->id,
                    'name' => $itemData['name'],
                    'amount' => $itemData['amount'],
                    'type' => $itemData['type'],
                    'assigned_to_id' => $itemData['assigned_to_id'] ?? null,
                ]);

                if ($itemData['type'] == 'assigned') {
                    if ($itemData['assigned_to_id'] !== Auth::id()) {
                        ExpenseItemSplit::create([
                            'expense_item_id' => $item->id,
                            'creditor_id' => Auth::id(),
                            'debtor_id' => $itemData['assigned_to_id'],
                            'amount' => $itemData['amount'],
                        ]);
                    }

                } else {
                    // equal split
                    $sharePerPerson = round($itemData['amount'] / $participantCount, 2);

                    foreach ($participantIds as $userId) {
                        if ($userId === Auth::id()) {
                            continue;
                        }

                        ExpenseItemSplit::create([
                            'expense_item_id' => $item->id,
                            'creditor_id' => Auth::id(),
                            'debtor_id' => $userId,
                            'amount' => $sharePerPerson,
                        ]);
                    }
                }

            }

            // tax and tip
            $extra = (float) $request->input('tax', 0) +
                        (float) $request->input('tip', 0);

            if ($extra > 0) {
                // virtual item for taxTip -> FIX
                $extraItem = ExpenseItem::create([
                    'expense_id' => $expense->id,
                    'name' => 'Tax & Tip',
                    'amount' => $extra,
                    'type' => 'equal',
                    'assigned_to_id' => null,
                ]);

                $extraSharePerPerson = round($extra / $participantCount, 2);

                foreach ($participantIds as $userId) {
                    if ($userId == Auth::id()) {
                        continue;
                    }

                    ExpenseItemSplit::create([
                        'expense_item_id' => $extraItem->id,
                        'creditor_id' => Auth::id(),
                        'debtor_id' => $userId,
                        'amount' => $extraSharePerPerson,
                    ]);

                }
            }

            DB::commit();

            $expense->load([
                'paidBy',
                'participants.user',
                'items.splits.creditor',
                'items.splits.debtor',
                'items.assignedTo',
            ]);

            return response()->json([
                'message' => 'Expense created successfully',
                'data' => new ExpenseResource($expense),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create expense',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function index(Request $request): JsonResponse{

        //get expenses where the user is creditor or a participant.
        //expenses where the user was involved
        $expenses = Expense::query()
                    ->where('paid_by_id', Auth::id())
                    ->orWhereHas('participants', function ($query){
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

    public function show(Expense $expense): JsonResponse{

    $isMemeber = $expense->paid_by_id === Auth::id() || $expense->participants()->where('user_id', Auth::id())->exists();

    if(!$isMemeber) {
        return response()->json([
            'message' => 'You do not have access to this expense',
        ], 403);
    }

    $expense->load([
        'paidBy',
        'participants.user',
        'items.assignedTo',
        'items.splits.creditor',
        'items.splits.debtor',
    ]);

    //ambiguity
    //$youOwe = $expense->splits()->where('debtor_id', Auth::id())->sum('amount');
    //$owedToYou = $expense->splits()->where('creditor_id', Auth::id())->sum('amount');

    $youOwe = $expense->splits()->where('debtor_id', Auth::id())->sum('expense_item_splits.amount');
    $owedToYou = $expense->splits()->where('creditor_id', Auth::id())->sum('expense_item_splits.amount');

    return response()->json([
        'data' => [
            'expense' => new ExpenseResource($expense),
            'your_summary' =>  [
                'you_owe' => round((float) $youOwe, 2),
                'owed_to_you' => round((float) $owedToYou, 2),
                'net'  => round((float) $owedToYou - (float) $youOwe, 2),
            ],
        ],
    ]);

    }
}
