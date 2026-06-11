<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use NumberToWords\NumberToWords;

class TemplateDocument extends Model
{
    protected $fillable = [
        'template_id', 'reference_no', 'title', 'document_date',
        'cheque_ids', 'voucher_ids', 'custom_fields',
        'final_content', 'status', 'printed_at', 'created_by',
    ];

    protected $casts = [
        'cheque_ids' => 'array',
        'voucher_ids' => 'array',
        'custom_fields' => 'array',
        'document_date' => 'date',
        'printed_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateReference(): string
    {
        return 'DOC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
    }

    /**
     * Generate cheque table HTML
     */
    private function generateChequeTable($cheques): string
    {
        if ($cheques->isEmpty()) return '';

        $html = '<table style="width:100%; border-collapse:collapse; margin:15px 0;">';
        $html .= '<thead><tr style="background:#f0f0f0;">';
        $html .= '<th style="border:1px solid #000; padding:5px;">S/L</th>';
        $html .= '<th style="border:1px solid #000; padding:5px;">Name</th>';
        $html .= '<th style="border:1px solid #000; padding:5px;">Cheque No</th>';
        $html .= '<th style="border:1px solid #000; padding:5px;">Date</th>';
        $html .= '<th style="border:1px solid #000; padding:5px;">Amount</th>';
        $html .= '<th style="border:1px solid #000; padding:5px;">Remark</th>';
        $html .= '</tr></thead><tbody>';

        $sl = 1;
        foreach ($cheques as $cheque) {
            $html .= '<tr>';
            $html .= '<td style="border:1px solid #000; padding:5px; text-align:center;">' . $sl++ . '</td>';
            $html .= '<td style="border:1px solid #000; padding:5px;">' . ($cheque->vendor->name ?? 'N/A') . '</td>';
            $html .= '<td style="border:1px solid #000; padding:5px; text-align:center;">' . $cheque->cheque_number . '</td>';
            $html .= '<td style="border:1px solid #000; padding:5px; text-align:center;">' . $cheque->cheque_date->format('d-m-Y') . '</td>';
            $html .= '<td style="border:1px solid #000; padding:5px; text-align:right;">' . number_format($cheque->amount, 0) . '</td>';
            $html .= '<td style="border:1px solid #000; padding:5px; text-align:center;">Cash</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Generate voucher table HTML
     */
    private function generateVoucherTable($vouchers): string
    {
        if ($vouchers->isEmpty()) return '';

        $html = '<table style="width:100%; border-collapse:collapse; margin:15px 0;">';
        $html .= '<thead><tr style="background:#f0f0f0;">';
        $html .= '<th style="border:1px solid #000; padding:5px;">S/L</th>';
        $html .= '<th style="border:1px solid #000; padding:5px;">Name</th>';
        $html .= '<th style="border:1px solid #000; padding:5px;">Purpose</th>';
        $html .= '<th style="border:1px solid #000; padding:5px;">Amount</th>';
        $html .= '<th style="border:1px solid #000; padding:5px;">Remark</th>';
        $html .= '</tr></thead><tbody>';

        $sl = 1;
        foreach ($vouchers as $voucher) {
            $html .= '<tr>';
            $html .= '<td style="border:1px solid #000; padding:5px; text-align:center;">' . $sl++ . '</td>';
            $html .= '<td style="border:1px solid #000; padding:5px;">' . ($voucher->vendor->name ?? 'N/A') . '</td>';
            $html .= '<td style="border:1px solid #000; padding:5px;">' . ($voucher->particulars ?? $voucher->voucher_name) . '</td>';
            $html .= '<td style="border:1px solid #000; padding:5px; text-align:right;">' . number_format($voucher->amount, 0) . '</td>';
            $html .= '<td style="border:1px solid #000; padding:5px; text-align:center;">Cash</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Convert amount to words
     */
    private function amountToWords($amount): string
    {
        $numberToWords = new NumberToWords();
        $transformer = $numberToWords->getNumberTransformer('en');
        return ucwords($transformer->toWords((int) $amount)) . ' Taka Only';
    }

    /**
     * Render final document content
     */
    public function renderContent(): string
    {
        $cheques = Cheque::whereIn('id', $this->cheque_ids ?? [])
            ->with(['vendor', 'bank'])->get();
        $vouchers = Voucher::whereIn('id', $this->voucher_ids ?? [])
            ->with('vendor')->get();

        $totalAmount = $cheques->sum('amount') ?: $vouchers->sum('amount');
        $totalCount = $cheques->count() ?: $vouchers->count();

        $chequeTable = $this->generateChequeTable($cheques);
        $voucherTable = $this->generateVoucherTable($vouchers);

        $data = [
            'DOCUMENT_DATE' => $this->document_date->format('d-m-Y'),
            'REFERENCE_NO' => $this->reference_no,
            'TITLE' => $this->title ?? $this->template->title ?? '',
            'COMPANY_NAME' => config('app.company_name', ''),
            'COMPANY_ADDRESS' => config('app.company_address', ''),
            'BANK_NAME' => $cheques->first()?->bank->name ?? '',
            'SUBJECT' => $this->custom_fields['subject'] ?? '',
            'CHEQUE_TABLE' => $chequeTable,
            'VOUCHER_TABLE' => $voucherTable,
            'TOTAL_AMOUNT' => number_format($totalAmount, 0),
            'TOTAL_AMOUNT_WORDS' => $this->amountToWords($totalAmount),
            'TOTAL_COUNT' => $totalCount,
            'SIGNATURE_NAME' => $this->custom_fields['signature_name'] ?? '',
            'SIGNATURE_TITLE' => $this->custom_fields['signature_title'] ?? '',
            'CUSTOM_TEXT' => $this->custom_fields['custom_text'] ?? '',
        ];

        return $this->template->render($data);
    }
}
