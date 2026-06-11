<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index()
    {
        return Template::where('is_active', true)->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:templates',
            'type' => 'required|string|max:50',
            'title' => 'nullable|string|max:500',
            'header' => 'nullable|string',
            'subject_template' => 'nullable|string',
            'body_template' => 'required|string',
            'footer' => 'nullable|string',
            'paper_size' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $template = Template::create($validated);

        return response()->json($template, 201);
    }

    public function show(Template $template)
    {
        return response()->json($template);
    }

    public function update(Request $request, Template $template)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:templates,name,' . $template->id,
            'type' => 'sometimes|string|max:50',
            'title' => 'nullable|string|max:500',
            'header' => 'nullable|string',
            'subject_template' => 'nullable|string',
            'body_template' => 'sometimes|string',
            'footer' => 'nullable|string',
            'paper_size' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $template->update($validated);

        return response()->json($template);
    }

    public function destroy(Template $template)
    {
        if ($template->documents()->exists()) {
            return response()->json(['message' => 'Template in use, cannot delete.'], 422);
        }
        $template->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function placeholders(Template $template)
    {
        return response()->json(Template::placeholders());
    }
}
