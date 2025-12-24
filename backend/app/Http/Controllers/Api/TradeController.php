<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trade;
use Illuminate\Http\JsonResponse;

class TradeController extends Controller
{
    /**
     * Get all trades for the authenticated user.
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $trades = Trade::query()
            ->where(function ($query) use ($user) {
                $query->where('buyer_id', $user->id)
                    ->orWhere('seller_id', $user->id);
            })
            ->with(['buyOrder', 'sellOrder', 'buyer', 'seller'])
            ->latest()
            ->get()
            ->map(function ($trade) use ($user) {
                return [
                    'id' => $trade->id,
                    'symbol' => $trade->symbol,
                    'amount' => $trade->amount,
                    'price' => $trade->price,
                    'side' => $trade->buyer_id === $user->id ? 'buy' : 'sell',
                    'created_at' => $trade->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'trades' => $trades,
        ]);
    }
}
