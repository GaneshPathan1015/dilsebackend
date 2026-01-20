<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class TaxRateController extends Controller
{
    /**
     * Show listing page
     */
    public function index()
    {
        return view('admin.tax_rates.index');
    }

    /**
     * DataTable data
     */
    public function data(Request $request)
    {
        $query = TaxRate::select([
            'id',
            'code',
            'name',
            'rate',
            'hsn_code',
            'status',
            'created_at'
        ]);

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                return ''; // handled in JS
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Store new tax rate
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:tax_rates,code'
            ],
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:999.99',
            'hsn_code' => 'nullable|string|max:20',
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        TaxRate::create($validated);

        return response()->json([
            'message' => 'Tax rate created successfully'
        ]);
    }

    /**
     * Fetch single tax rate (for edit)
     */
    public function edit($id)
    {
        $taxRate = TaxRate::findOrFail($id);

        return response()->json($taxRate);
    }

    /**
     * Update tax rate
     */
    public function update(Request $request, $id)
    {
        $taxRate = TaxRate::findOrFail($id);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('tax_rates', 'code')->ignore($taxRate->id)
            ],
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:999.99',
            'hsn_code' => 'nullable|string|max:20',
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $taxRate->update($validated);

        return response()->json([
            'message' => 'Tax rate updated successfully'
        ]);
    }

    /**
     * Delete tax rate (soft delete)
     */
    public function destroy($id)
    {
        $taxRate = TaxRate::findOrFail($id);

        // ✅ UPDATED: Use new relationships
        if ($taxRate->productVariations()->exists()) {
            return response()->json([
                'message' => 'This tax rate is in use in product variations and cannot be deleted'
            ], 400);
        }

        if ($taxRate->diamonds()->exists()) {
            return response()->json([
                'message' => 'This tax rate is in use in diamonds and cannot be deleted'
            ], 400);
        }

        $taxRate->delete();

        return response()->json([
            'message' => 'Tax rate deleted successfully'
        ]);
    }
} 