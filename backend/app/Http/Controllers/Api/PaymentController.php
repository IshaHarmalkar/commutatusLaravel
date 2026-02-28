<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function store(StorePaymentRequest $request, PaymentService $paymentService): JsonResponse
    {
        try {
            $result = $paymentService->recordPayment(
                Auth::id(),
                (int) $request->creditor_id,
                (float) $request->amount,
                $request->input('notes')
            );

            return response()->json([
                'message' => 'Payment recorded successfully',
                'data' => new PaymentResource($result['payment']),
                'balance_after' => [
                    'you_owe' => $result['balance_after'],
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function index(): JsonResponse
    {
        $user = AUth::id();

        $payments = Payment::forUser(Auth::id())
            ->with('fromUser', 'toUser')
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => PaymentResource::collection($payments),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);

    }
}
