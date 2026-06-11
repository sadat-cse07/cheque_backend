<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TemplateDocument;
use Illuminate\Http\Request;

class TemplateDocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = TemplateDocument::with('template');

        if ($request->filled('template_id')) {
            $query->where('template_id', $request->template_id);
        }

        return $query->latest()->paginate(25);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:templates,id',
            'title' => 'nullable|string|max:500',
            'document_date' => 'required|date',
            'cheque_ids' => 'nullable|array',
            'voucher_ids' => 'nullable|array',
            'custom_fields' => 'nullable|array',
        ]);

        $validated['reference_no'] = TemplateDocument::generateReference();
        $validated['status'] = 'draft';
        $validated['created_by'] = $request->user()?->id;

        $document = TemplateDocument::create($validated);

        return response()->json($document->load('template'), 201);
    }

    public function show(TemplateDocument $document)
    {
        return response()->json($document->load('template'));
    }

    public function generate(TemplateDocument $document)
    {
        $finalContent = $document->renderContent();
        $document->update([
            'final_content' => $finalContent,
            'status' => 'generated',
        ]);

        return response()->json([
            'document' => $document->fresh(),
            'html' => $finalContent,
        ]);
    }

    public function printData(TemplateDocument $document)
    {
        if (!$document->final_content) {
            $document->final_content = $document->renderContent();
            $document->save();
        }

        return response()->json([
            'document' => $document->load('template'),
            'html' => $document->final_content,
        ]);
    }

    public function bulkPrint(Request $request)
    {
        $ids = $request->input('ids', []);
        $documents = TemplateDocument::with('template')->whereIn('id', $ids)->get();

        $html = '';
        foreach ($documents as $doc) {
            if (!$doc->final_content) {
                $doc->final_content = $doc->renderContent();
                $doc->save();
            }
            $html .= '<div style="page-break-after:always;">' . $doc->final_content . '</div>';
        }

        TemplateDocument::whereIn('id', $ids)->update([
            'status' => 'printed',
            'printed_at' => now(),
        ]);

        return response()->json(['html' => $html]);
    }
}
