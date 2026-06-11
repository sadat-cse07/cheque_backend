<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    protected $fillable = [
        'name', 'type', 'title', 'header', 'subject_template',
        'body_template', 'footer', 'paper_size', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(TemplateDocument::class);
    }

    /**
     * All available placeholders for templates
     */
    public static function placeholders(): array
    {
        return [
            '{{DOCUMENT_DATE}}' => 'Document date',
            '{{REFERENCE_NO}}' => 'Auto-generated reference number',
            '{{TITLE}}' => 'Document title',
            '{{COMPANY_NAME}}' => 'Company name',
            '{{COMPANY_ADDRESS}}' => 'Company address',
            '{{BANK_NAME}}' => 'Bank name',
            '{{SUBJECT}}' => 'Subject line',
            '{{CHEQUE_TABLE}}' => 'Table of selected cheques',
            '{{VOUCHER_TABLE}}' => 'Table of selected vouchers',
            '{{TOTAL_AMOUNT}}' => 'Total amount',
            '{{TOTAL_AMOUNT_WORDS}}' => 'Total amount in words',
            '{{TOTAL_COUNT}}' => 'Number of items',
            '{{SIGNATURE_NAME}}' => 'Signatory name',
            '{{SIGNATURE_TITLE}}' => 'Signatory title',
            '{{CUSTOM_TEXT}}' => 'Custom text field',
        ];
    }

    /**
     * Render template with data
     */
    public function render(array $data): string
    {
        $html = '<div class="template-document" style="font-family: Arial, sans-serif; padding: 20px;">';

        // Header
        if ($this->header) {
            $html .= '<div class="header">' . $this->replacePlaceholders($this->header, $data) . '</div>';
        }

        // Title
        if ($this->title) {
            $html .= '<h2 style="text-align:center; margin: 20px 0;">' . $this->replacePlaceholders($this->title, $data) . '</h2>';
        }

        // Date & Reference
        $html .= '<div style="display:flex; justify-content:space-between; margin-bottom:20px;">';
        $html .= '<div><strong>Ref: </strong>' . ($data['REFERENCE_NO'] ?? '') . '</div>';
        $html .= '<div><strong>Date: </strong>' . ($data['DOCUMENT_DATE'] ?? '') . '</div>';
        $html .= '</div>';

        // Subject
        if ($this->subject_template) {
            $html .= '<div class="subject" style="margin-bottom:20px;">';
            $html .= '<strong>' . $this->replacePlaceholders($this->subject_template, $data) . '</strong>';
            $html .= '</div>';
        }

        // Body
        $html .= '<div class="body">' . $this->replacePlaceholders($this->body_template, $data) . '</div>';

        // Footer
        if ($this->footer) {
            $html .= '<div class="footer" style="margin-top:30px;">' . $this->replacePlaceholders($this->footer, $data) . '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    private function replacePlaceholders(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value ?? '', $content);
        }
        return $content;
    }
}
