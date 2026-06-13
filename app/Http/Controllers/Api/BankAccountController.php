<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Bank;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = BankAccount::with('bank');

        if ($request->filled('bank_id')) {
            $query->where('bank_id', $request->bank_id);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return $query->orderBy('bank_id')->orderBy('account_number')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'account_number' => 'required|string|max:50|unique:bank_accounts,account_number,NULL,id,bank_id,' . $request->bank_id,
            'account_name' => 'nullable|string|max:255',
            'account_type' => 'nullable|string|max:50',
            'branch' => 'nullable|string|max:255',
            'opening_balance' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ], [
            'account_number.unique' => 'This account number already exists for this bank.',
        ]);

        $validated['current_balance'] = $validated['opening_balance'] ?? 0;
        $account = BankAccount::create($validated);

        return response()->json([
            'message' => 'Bank account added successfully',
            'account' => $account->load('bank'),
        ], 201);
    }

    public function show(BankAccount $bankAccount)
    {
        return response()->json($bankAccount->load('bank'));
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $validated = $request->validate([
            'account_number' => 'sometimes|string|max:50|unique:bank_accounts,account_number,' . $bankAccount->id . ',id,bank_id,' . ($request->bank_id ?? $bankAccount->bank_id),
            'account_name' => 'nullable|string|max:255',
            'account_type' => 'nullable|string|max:50',
            'branch' => 'nullable|string|max:255',
            'opening_balance' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $bankAccount->update($validated);

        return response()->json([
            'message' => 'Bank account updated',
            'account' => $bankAccount->fresh()->load('bank'),
        ]);
    }

    public function destroy(BankAccount $bankAccount)
    {
        if ($bankAccount->cheques()->exists()) {
            return response()->json(['message' => 'Cannot delete account with existing cheques.'], 422);
        }

        $bankAccount->delete();
        return response()->json(['message' => 'Bank account deleted']);
    }

    /**
     * Get accounts by bank ID
     */
    public function byBank(Bank $bank)
    {
        return $bank->accounts()->active()->orderBy('account_number')->get();
    }
}
