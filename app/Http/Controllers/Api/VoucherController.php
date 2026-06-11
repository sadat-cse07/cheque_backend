<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    /**
     * Display a listing of vouchers with advanced filters.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', Voucher::class);

        $query = Voucher::with('vendor');

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('voucher_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('voucher_date', '<=', $request->date_to);
        }

        // Vendor filter
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Voucher name search
        if ($request->filled('voucher_name')) {
            $query->where('voucher_name', 'like', '%' . $request->voucher_name . '%');
        }

        // Payment status filter
        if ($request->filled('is_paid')) {
            $query->where('is_paid', $request->boolean('is_paid'));
        }

        // Amount range
        if ($request->filled('amount_min')) {
            $query->where('amount', '>=', $request->amount_min);
        }
        if ($request->filled('amount_max')) {
            $query->where('amount', '<=', $request->amount_max);
        }

        // Get specific IDs (used by cheque form to load selected vouchers)
        if ($request->filled('ids')) {
            $ids = array_map('intval', explode(',', $request->ids));
            $query->whereIn('id', $ids);
        }

        // Sorting
        $sortField = $request->get('sort_by', 'voucher_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $allowedSorts = ['voucher_date', 'voucher_name', 'amount', 'created_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        }

        // Exclude already paid vouchers by default when selecting for payment
        if ($request->boolean('exclude_paid', true) && !$request->filled('is_paid')) {
            $query->where('is_paid', false);
        }

        return $query->paginate($request->get('per_page', 50));
    }

    /**
     * Store a newly created voucher.
     */
    public function store(Request $request)
    {
        // $this->authorize('create', Voucher::class);

        $validated = $request->validate([
            'voucher_date'  => 'required|date',
            'particulars'   => 'nullable|string|max:2000',
            'voucher_name'  => 'required|string|max:255',
            'vendor_id'     => 'required|exists:vendors,id',
            'amount'        => 'required|numeric|min:0.01|max:999999999.99',
        ], [
            'vendor_id.exists' => 'The selected vendor does not exist.',
            'amount.min'       => 'The amount must be greater than zero.',
            'amount.max'       => 'The amount exceeds the maximum allowed.',
        ]);

        // Check if vendor is active
        $vendor = Vendor::find($validated['vendor_id']);
        if ($vendor && $vendor->status !== 'active') {
            return response()->json([
                'message' => 'Cannot create voucher for an inactive vendor.',
            ], 422);
        }

        $voucher = Voucher::create($validated);

        return response()->json([
            'message' => 'Voucher created successfully.',
            'voucher' => $voucher->load('vendor'),
        ], 201);
    }

    /**
     * Display the specified voucher.
     */
    public function show(Voucher $voucher)
    {
        // $this->authorize('view', $voucher);

        $voucher->load(['vendor', 'cheques']);

        return response()->json($voucher);
    }

    /**
     * Update the specified voucher.
     * Only allowed if voucher is not paid.
     */
    public function update(Request $request, Voucher $voucher)
    {
        // $this->authorize('update', $voucher);

        if ($voucher->is_paid) {
            return response()->json([
                'message' => 'Cannot update a paid voucher. Void the associated cheque first.',
            ], 422);
        }

        $validated = $request->validate([
            'voucher_date'  => 'sometimes|date',
            'particulars'   => 'nullable|string|max:2000',
            'voucher_name'  => 'sometimes|string|max:255',
            'vendor_id'     => 'sometimes|exists:vendors,id',
            'amount'        => 'sometimes|numeric|min:0.01|max:999999999.99',
        ]);

        $voucher->update($validated);

        return response()->json([
            'message' => 'Voucher updated successfully.',
            'voucher' => $voucher->fresh()->load('vendor'),
        ]);
    }

    /**
     * Remove the specified voucher.
     * Only allowed if voucher is not paid and not attached to any cheque.
     */
    public function destroy(Voucher $voucher)
    {
        // $this->authorize('delete', $voucher);

        if ($voucher->is_paid) {
            return response()->json([
                'message' => 'Cannot delete a paid voucher. Void the associated cheque first.',
            ], 422);
        }

        if ($voucher->cheques()->exists()) {
            return response()->json([
                'message' => 'Voucher is attached to a cheque. Cannot delete.',
            ], 422);
        }

        $voucher->delete();

        return response()->json([
            'message' => 'Voucher deleted successfully.',
        ]);
    }

    /**
     * Get unpaid vouchers grouped by vendor (for quick payment selection).
     */
    public function unpaidByVendor(Request $request)
    {
        $vouchers = Voucher::with('vendor')
            ->where('is_paid', false)
            ->when($request->vendor_id, function ($q, $vendorId) {
                return $q->where('vendor_id', $vendorId);
            })
            ->orderBy('vendor_id')
            ->orderBy('voucher_date')
            ->get()
            ->groupBy('vendor_id');

        return response()->json($vouchers);
    }
}
