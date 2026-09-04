<?php

namespace App\Http\Controllers;

use App\Models\PaymentWallet;
use App\Services\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function update(Request $request, WalletService $wallets)
    {
        $validated = $request->validate([
            'payment_method' => 'required|string|max:50',
            'is_enabled' => 'nullable|boolean',
            'opening_balance' => 'nullable|numeric',
            'opening_as_of' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $wallet = PaymentWallet::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'payment_method' => $validated['payment_method'],
            ],
            [
                'is_enabled' => $request->boolean('is_enabled'),
                'opening_balance' => $validated['opening_balance'] ?? 0,
                'opening_as_of' => $validated['opening_as_of'] ?? now()->toDateString(),
                'notes' => $validated['notes'] ?? null,
            ]
        );

        $wallets->ensureDefaults($request->user()->id);

        return back()->with('success', $wallet->payment_method.' wallet updated.');
    }
}
