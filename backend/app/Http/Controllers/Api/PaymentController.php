<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Models\ExpenseItemSplit;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $me = Auth::id();
        $toUserId = (int) $request->creditor_id;
        $amount = (float) $request->amount;

        // what user owes
        $oweFromSplits = (float) ExpenseItemSplit::where('creditor_id', $toUserId)
            ->where('debtor_id', $me)
            ->sum('amount');

        $friendOweFromSplits = (float) ExpenseItemSplit::where('creditor_id', $me)
            ->where('debtor_id', $toUserId)
            ->sum('amount');

        $alreadyPaid = (float) Payment::where('debtor_id', $me)
            ->where('creditor_id', $toUserId)
            ->sum('amount');

        $friendAlreadyPaid = (float) Payment::where('debtor_id', $toUserId)
            ->where('creditor_id', $me)
            ->sum('amount');

        $net = ($oweFromSplits - $alreadyPaid) - ($friendOweFromSplits - $friendAlreadyPaid);

        if ($net <= 0) {
            return response()->json([

                'message' => 'You do not owe this person anything',
            ], 422);

        }

        /*     // Floating point comparison buffer (0.01) to prevent "You owe 116.670000001" errors
        if ($amount > ($net + 0.01)) {
            return response()->json(['message' => 'You are overpaying. You only owe ' . round($net, 2) . '.'], 422);
        }
 */
        if ($amount > $net) {
            return response()->json([
                'message' => 'You are overpaying. You only owe '.round($net, 2).'.',

            ], 422);
        }

        $payment = Payment::create([
            'debtor_id' => $me,
            'creditor_id' => $toUserId,
            'amount' => $amount,
            'notes' => $request->input('notes'),
        ]);

        $toUser = User::find($toUserId);

        return response()->json([
            'message' => 'Payment recorded successfully.',
            'data' => [
                'id' => $payment->id,
                'from' => ['id' => $me, 'name' => Auth::user()->name],
                'to' => ['id' => $toUserId, 'name' => $toUser->name],
                'amount' => $payment->amount,
                'notes' => $payment->notes,
                'created_at' => $payment->created_at->toDateTimeString(),

            ],
            'balance_after' => [
                'you_owe' => round(max(0, $net - $amount), 2),
            ],

        ], 201);

    }

    public function index(): JsonResponse
    {
        $me = AUth::id();

        $payments = Payment::where('debtor_id', $me)
            ->orWhere('creditor_id', $me)
            ->with('fromUser', 'toUser')
            ->latest()
            ->paginate(15);

        $formatted = $payments->getCollection()->map(fn ($payment) => [
            'id' => $payment->id,
            'from' => ['id' => $payment->fromUser->id, 'name' => $payment->fromUser->name],
            'to' => ['id' => $payment->toUser->id, 'name' => $payment->toUser->name],
            'amount' => $payment->amount,
            'notes' => $payment->notes,
            'direction' => $payment->from_user_id === $me ? 'sent' : 'received',
            'created_at' => $payment->created_at->toDateTimeString(),
        ]);

        return response()->json([
            'data' => $formatted,
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);

    }
}
