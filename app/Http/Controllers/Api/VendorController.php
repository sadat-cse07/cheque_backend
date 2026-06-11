<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VendorController extends Controller
{
    /**
     * Display a listing of vendors with optional filters.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', Vendor::class);

        $query = Vendor::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortField = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $allowedSorts = ['name', 'created_at', 'status'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        }

        // Include unpaid amount?
        if ($request->boolean('with_amounts')) {
            $query->withCount(['vouchers as unpaid_amount' => function ($q) {
                $q->select(\DB::raw('COALESCE(SUM(amount), 0)'))
                    ->where('is_paid', false);
            }]);
        }

        return $query->paginate($request->get('per_page', 25));
    }

    /**
     * Store a newly created vendor.
     */
    public function store(Request $request)
    {
        // $this->authorize('create', Vendor::class);

        $validated = $request->validate([
            'name'            => 'required|string|max:255|unique:vendors,name',
            'address'         => 'nullable|string|max:1000',
            'contact_person'  => 'nullable|string|max:255',
            'phone'           => 'nullable|string|max:20|unique:vendors,phone',
            'email'           => 'nullable|email|max:255|unique:vendors,email',
            'status'          => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $vendor = Vendor::create($validated);

        return response()->json([
            'message' => 'Vendor created successfully.',
            'vendor'  => $vendor,
        ], 201);
    }

    /**
     * Display the specified vendor.
     */
    public function show(Vendor $vendor)
    {
        // $this->authorize('view', $vendor);

        $vendor->loadCount('vouchers');
        $vendor->loadSum(['vouchers as total_unpaid' => function ($q) {
            $q->where('is_paid', false);
        }], 'amount');

        return response()->json($vendor);
    }

    /**
     * Update the specified vendor.
     */
    public function update(Request $request, Vendor $vendor)
    {
        // $this->authorize('update', $vendor);

        $validated = $request->validate([
            'name'            => ['sometimes', 'string', 'max:255', Rule::unique('vendors')->ignore($vendor->id)],
            'address'         => 'nullable|string|max:1000',
            'contact_person'  => 'nullable|string|max:255',
            'phone'           => ['nullable', 'string', 'max:20', Rule::unique('vendors')->ignore($vendor->id)],
            'email'           => ['nullable', 'email', 'max:255', Rule::unique('vendors')->ignore($vendor->id)],
            'status'          => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $vendor->update($validated);

        return response()->json([
            'message' => 'Vendor updated successfully.',
            'vendor'  => $vendor->fresh(),
        ]);
    }

    /**
     * Remove the specified vendor.
     * Only allowed if vendor has no vouchers or cheques.
     */
    public function destroy(Vendor $vendor)
    {
        // $this->authorize('delete', $vendor);

        if ($vendor->vouchers()->exists() || $vendor->cheques()->exists()) {
            return response()->json([
                'message' => 'Cannot delete vendor with existing vouchers or cheques. Mark them as inactive instead.',
            ], 422);
        }

        $vendor->delete();

        return response()->json([
            'message' => 'Vendor deleted successfully.',
        ]);
    }
}
