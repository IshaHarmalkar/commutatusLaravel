<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BalanceController extends Controller
{
    public function index(BalanceService $balanceService): JsonResponse
    {
        Log::info('BalanceController@index was hit by User: '.Auth::id());

        $summary = $balanceService->getBalanceSummary(Auth::id());

        return response()->json([
            'data' => $summary,
        ]);
    }
}
