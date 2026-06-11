<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BankController extends Controller
{
    /**
     * Display a listing of banks.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', Bank::class);

        $query = Bank::query();

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('branch', 'like', "%{$search}%")
                    ->orWhere('ifsc_code', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->paginate($request->get('per_page', 25));
    }

    /**
     * Store a newly created bank.
     */
    public function store(Request $request)
    {
        // $this->authorize('create', Bank::class);

        $validated = $request->validate([
            'name'      => 'required|string|max:255|unique:banks,name',
            'branch'    => 'nullable|string|max:255',
            'ifsc_code' => 'nullable|string|max:20|unique:banks,ifsc_code',
            'alignment' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        // Set default alignment if not provided
        if (!isset($validated['alignment'])) {
            $validated['alignment'] = Bank::defaultAlignment();
        }

        $bank = Bank::create($validated);

        return response()->json([
            'message' => 'Bank created successfully.',
            'bank'    => $bank,
        ], 201);
    }

    /**
     * Display the specified bank.
     */
    public function show(Bank $bank)
    {
        // $this->authorize('view', $bank);

        $bank->loadCount('cheques');

        return response()->json($bank);
    }

    /**
     * Update the specified bank.
     */
    public function update(Request $request, Bank $bank)
    {
        // $this->authorize('update', $bank);

        $validated = $request->validate([
            'name'      => ['sometimes', 'string', 'max:255', Rule::unique('banks')->ignore($bank->id)],
            'branch'    => 'nullable|string|max:255',
            'ifsc_code' => ['nullable', 'string', 'max:20', Rule::unique('banks')->ignore($bank->id)],
            'alignment' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $bank->update($validated);

        return response()->json([
            'message' => 'Bank updated successfully.',
            'bank'    => $bank->fresh(),
        ]);
    }

    /**
     * Remove the specified bank.
     */
    public function destroy(Bank $bank)
    {
        // $this->authorize('delete', $bank);

        if ($bank->cheques()->exists()) {
            return response()->json([
                'message' => 'Cannot delete bank with existing cheques. Mark it as inactive instead.',
            ], 422);
        }

        $bank->delete();

        return response()->json([
            'message' => 'Bank deleted successfully.',
        ]);
    }
}
