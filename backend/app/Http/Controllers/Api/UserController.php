<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    // for adding participants for expenses
    public function search(Request $request): JsonResponse
    {
        $query = User::where('id', '!=', Auth::id())
            ->select('id', 'name', 'email');

        if ($request->filled('q')) {
            $searchTerm = '%'.$request->q.'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm);
            });
        }

        $users = $query->limit(15)->get();

        return response()->json(['data' => $users]);
    }

    // expenses per user
    public function expenses(User $user): JsonResponse
    {
        $currentUserId = Auth::id();

        $hasSharedExpense = Expense::involvingUser($currentUserId)
            ->involvingUser($user->id)
            ->exists();

        if (! $hasSharedExpense) {
            return response()->json([
                'message' => 'You do not have access to this user\'s expenses',
            ], 403);
        }

        // get all expense this friend was involved in
        $expenses = Expense::involvingUser($user->id)
            ->with([
                'paidBy',
                'participants.user',
                'items.splits.creditor',
                'items.splits.debtor',
            ])->latest()->paginate(15);

        return response()->json([
            'friend' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'data' => ExpenseResource::collection($expenses->items()),
            'meta' => [
                'current_page' => $expenses->currentPage(),
                'last_page' => $expenses->lastPage(),
                'per_page' => $expenses->perPage(),
                'total' => $expenses->total(),
            ],
        ]);

    }
}
