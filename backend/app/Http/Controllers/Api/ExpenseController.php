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
            $participantIds = collect($request->participants_ids)->push(Auth::id())->unique()->values()->toArray();

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
}
