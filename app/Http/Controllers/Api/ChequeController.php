<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cheque;
use App\Models\Voucher;
use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ChequeController extends Controller
{
    /**
     * Display a listing of cheques with advanced filters.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', Cheque::class);

        $query = Cheque::with(['vendor', 'bank', 'vouchers']);

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('cheque_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('cheque_date', '<=', $request->date_to);
        }

        // Vendor filter
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Bank filter
        if ($request->filled('bank_id')) {
            $query->where('bank_id', $request->bank_id);
        }

        // Cheque number search
        if ($request->filled('cheque_number')) {
            $query->where('cheque_number', 'like', '%' . $request->cheque_number . '%');
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Amount range
        if ($request->filled('amount_min')) {
            $query->where('amount', '>=', $request->amount_min);
        }
        if ($request->filled('amount_max')) {
            $query->where('amount', '<=', $request->amount_max);
        }

        // Sorting
        $sortField = $request->get('sort_by', 'cheque_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $allowedSorts = ['cheque_date', 'amount', 'cheque_number', 'created_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        }

        return $query->paginate($request->get('per_page', 50));
    }

    /**
     * Store a newly created cheque (payment).
     * Includes comprehensive validation and transaction handling.
     */
    public function store(Request $request)
    {
        // $this->authorize('create', Cheque::class);

        $validated = $request->validate([
            'bank_id'          => 'required|exists:banks,id',
            'cheque_number'    => 'required|string|max:50',
            'cheque_date'      => 'required|date',
            'vendor_id'        => 'required|exists:vendors,id',
            'amount'           => 'required|numeric|min:0.01|max:999999999.99',
            'amount_in_words'  => 'required|string|max:500',
            'voucher_ids'      => 'required|array|min:1',
            'voucher_ids.*'    => 'integer|exists:vouchers,id',
        ], [
            'voucher_ids.min' => 'At least one voucher must be selected.',
            'voucher_ids.*.exists' => 'One or more selected vouchers are invalid.',
        ]);

        // Additional validation: Bank must be active
        $bank = Bank::find($validated['bank_id']);
        if (!$bank || !$bank->is_active) {
            return response()->json([
                'message' => 'The selected bank is inactive or does not exist.',
            ], 422);
        }

        // Check duplicate cheque number for the same bank
        $existingCheque = Cheque::where('bank_id', $validated['bank_id'])
            ->where('cheque_number', $validated['cheque_number'])
            ->where('status', '!=', 'voided')
            ->first();
        if ($existingCheque) {
            return response()->json([
                'message' => 'This cheque number already exists for the selected bank.',
            ], 422);
        }

        // Remove duplicate voucher IDs
        $voucherIds = array_unique($validated['voucher_ids']);

        // Fetch vouchers
        $vouchers = Voucher::whereIn('id', $voucherIds)->get();

        // Validate all vouchers exist
        if ($vouchers->count() !== count($voucherIds)) {
            return response()->json([
                'message' => 'One or more voucher IDs are invalid.',
            ], 422);
        }

        // Validate all vouchers belong to the specified vendor
        $voucherVendorIds = $vouchers->pluck('vendor_id')->unique();
        if ($voucherVendorIds->count() !== 1) {
            return response()->json([
                'message' => 'All selected vouchers must belong to the same vendor.',
            ], 422);
        }
        if ($voucherVendorIds->first() != $validated['vendor_id']) {
            return response()->json([
                'message' => 'All selected vouchers must belong to the cheque payee vendor.',
            ], 422);
        }

        // Validate amount matches (with decimal tolerance)
        $totalAmount = $vouchers->sum('amount');
        if (abs($totalAmount - $validated['amount']) > 0.005) {
            return response()->json([
                'message'          => 'Cheque amount does not match the total of selected vouchers.',
                'voucher_total'    => round($totalAmount, 2),
                'cheque_amount'    => round($validated['amount'], 2),
                'difference'       => round(abs($totalAmount - $validated['amount']), 2),
            ], 422);
        }

        // Validate no voucher is already paid
        $alreadyPaid = $vouchers->where('is_paid', true);
        if ($alreadyPaid->isNotEmpty()) {
            return response()->json([
                'message'       => 'Some selected vouchers have already been paid.',
                'paid_vouchers' => $alreadyPaid->pluck('voucher_name')->toArray(),
            ], 422);
        }

        // Persist in transaction
        try {
            $cheque = DB::transaction(function () use ($validated, $voucherIds) {
                // Create cheque
                $cheque = Cheque::create([
                    'bank_id'         => $validated['bank_id'],
                    'cheque_number'   => $validated['cheque_number'],
                    'cheque_date'     => $validated['cheque_date'],
                    'vendor_id'       => $validated['vendor_id'],
                    'amount'          => $validated['amount'],
                    'amount_in_words' => $validated['amount_in_words'],
                    'status'          => 'active',
                ]);

                // Attach vouchers
                $cheque->vouchers()->attach($voucherIds);

                // Mark all attached vouchers as paid
                Voucher::whereIn('id', $voucherIds)->update(['is_paid' => true]);

                return $cheque;
            });

            return response()->json([
                'message'   => 'Cheque created successfully.',
                'cheque_id' => $cheque->id,
                'cheque'    => $cheque->load(['vendor', 'bank', 'vouchers']),
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Cheque creation failed: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'data'    => $validated,
            ]);

            return response()->json([
                'message' => 'An unexpected error occurred while creating the cheque. Please try again.',
            ], 500);
        }
    }

    /**
     * Display the specified cheque with all relationships.
     */
    public function show(Cheque $cheque)
    {
        // $this->authorize('view', $cheque);

        $cheque->load(['vendor', 'bank', 'vouchers.vendor']);

        return response()->json($cheque);
    }

    /**
     * Void a cheque and release all attached vouchers.
     */
    public function destroy(Request $request, Cheque $cheque)
    {
        // $this->authorize('delete', $cheque);

        if (!$cheque->isVoidable()) {
            return response()->json([
                'message' => 'This cheque is already voided and cannot be voided again.',
            ], 422);
        }

        $reason = $request->input('reason', '');

        try {
            $cheque->void($reason);

            return response()->json([
                'message' => 'Cheque voided successfully. All vouchers are now available for payment.',
            ]);

        } catch (\Exception $e) {
            \Log::error('Cheque void failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to void cheque. Please try again.',
            ], 500);
        }
    }

    /**
     * Return data required for printing a single cheque.
     * Includes decoded bank alignment for direct frontend use.
     */
    public function printData(Cheque $cheque)
    {
        // $this->authorize('view', $cheque);

        if ($cheque->status === 'voided') {
            return response()->json([
                'message' => 'Cannot print a voided cheque.',
            ], 422);
        }

        $cheque->load(['vendor', 'bank', 'vouchers']);

        // Ensure bank alignment is decoded (if it's a string)
        if ($cheque->bank && is_string($cheque->bank->alignment)) {
            $cheque->bank->alignment = json_decode($cheque->bank->alignment, true);
        }

        // Mark as printed
        $cheque->markAsPrinted();

        return response()->json($cheque);
    }

    /**
     * Bulk print data.
     * Accepts GET with ?ids=1,2,3 or POST with {ids:[1,2,3]}.
     */
    public function bulkPrintData(Request $request)
    {
        $ids = [];

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'ids'   => 'required|array|min:1|max:100',
                'ids.*' => 'integer|exists:cheques,id',
            ], [
                'ids.max' => 'Maximum 100 cheques can be printed at once.',
            ]);
            $ids = $validated['ids'];
        } else {
            // GET: ?ids=1,2,3
            $validated = $request->validate([
                'ids' => 'required|string',
            ]);
            $ids = array_filter(array_map('intval', explode(',', $validated['ids'])));

            if (empty($ids)) {
                return response()->json(['message' => 'No valid cheque IDs provided.'], 422);
            }

            if (count($ids) > 100) {
                return response()->json(['message' => 'Maximum 100 cheques can be printed at once.'], 422);
            }

            $existingCount = Cheque::whereIn('id', $ids)->count();
            if ($existingCount !== count($ids)) {
                return response()->json(['message' => 'One or more cheque IDs are invalid.'], 422);
            }
        }

        $cheques = Cheque::with(['vendor', 'bank'])
            ->whereIn('id', $ids)
            ->orderBy('cheque_date')
            ->get();

        // Filter out voided cheques
        $voidedCheques = $cheques->where('status', 'voided');
        if ($voidedCheques->isNotEmpty()) {
            return response()->json([
                'message'        => 'Some selected cheques are voided and cannot be printed.',
                'voided_cheques' => $voidedCheques->pluck('cheque_number')->toArray(),
            ], 422);
        }

        // Decode alignment for each bank
        foreach ($cheques as $cheque) {
            if ($cheque->bank && is_string($cheque->bank->alignment)) {
                $cheque->bank->alignment = json_decode($cheque->bank->alignment, true);
            }
        }

        // Mark all as printed
        Cheque::whereIn('id', $ids)->update(['printed_at' => now()]);

        return response()->json($cheques);
    }

    /**
     * Get cheque statistics (summary).
     */
    public function stats(Request $request)
    {
        $query = Cheque::query();

        if ($request->filled('date_from')) {
            $query->where('cheque_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('cheque_date', '<=', $request->date_to);
        }

        $stats = [
            'total_count'    => $query->count(),
            'total_amount'   => $query->sum('amount'),
            'active_count'   => (clone $query)->where('status', 'active')->count(),
            'active_amount'  => (clone $query)->where('status', 'active')->sum('amount'),
            'voided_count'   => (clone $query)->where('status', 'voided')->count(),
            'voided_amount'  => (clone $query)->where('status', 'voided')->sum('amount'),
            'printed_count'  => (clone $query)->whereNotNull('printed_at')->count(),
        ];

        return response()->json($stats);
    }
}
