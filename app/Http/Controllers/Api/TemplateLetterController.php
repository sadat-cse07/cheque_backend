<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TemplateLetter;
use App\Models\Template;
use Illuminate\Http\Request;

class TemplateLetterController extends Controller
{
    public function index(Request $request)
    {
        $query = TemplateLetter::with(['template', 'creator']);

        if ($request->filled('template_id')) {
            $query->where('template_id', $request->template_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $query->latest()->paginate(25);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:templates,id',
            'subject' => 'nullable|string|max:500',
            'cheque_ids' => 'nullable|array',
            'cheque_ids.*' => 'exists:cheques,id',
            'voucher_ids' => 'nullable|array',
            'voucher_ids.*' => 'exists:vouchers,id',
            'custom_data' => 'nullable|array',
        ]);

        $validated['reference_number'] = TemplateLetter::generateReference();
        $validated['status'] = 'draft';
        $validated['created_by'] = $request->user()?->id;

        $letter = TemplateLetter::create($validated);

        return response()->json([
            'message' => 'Letter created successfully',
            'letter' => $letter->load('template'),
        ], 201);
    }

    public function show(TemplateLetter $letter)
    {
        $letter->load(['template', 'creator']);
        return response()->json($letter);
    }

    public function generate(TemplateLetter $letter)
    {
        $finalContent = $letter->renderContent();
        $letter->update([
            'final_content' => $finalContent,
            'status' => 'generated',
        ]);

        return response()->json([
            'message' => 'Letter generated successfully',
            'letter' => $letter->fresh(),
            'html' => $finalContent,
        ]);
    }

    public function print(TemplateLetter $letter)
    {
        if (!$letter->final_content) {
            $letter->renderContent();
            $letter->save();
        }

        return response()->json([
            'letter' => $letter->load('template'),
            'html' => $letter->final_content,
        ]);
    }

    public function bulkPrint(Request $request)
    {
        $ids = $request->input('ids', []);
        $letters = TemplateLetter::with('template')->whereIn('id', $ids)->get();

        $html = '';
        foreach ($letters as $letter) {
            if (!$letter->final_content) {
                $letter->final_content = $letter->renderContent();
                $letter->save();
            }
            $html .= '<div style="page-break-after: always;">' . $letter->final_content . '</div>';
        }

        TemplateLetter::whereIn('id', $ids)->update(['status' => 'printed', 'printed_at' => now()]);

        return response()->json(['html' => $html]);
    }
}
