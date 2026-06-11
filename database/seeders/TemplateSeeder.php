<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        Template::create([
            'name' => 'Fund Requisition Form',
            'type' => 'fund_requisition',
            'title' => 'FUND REQUISITION FORM',
            'header' => '<div style="text-align:center; margin-bottom:20px;">
                <p>{{COMPANY_ADDRESS}}</p>
                <p><strong>[ {{TITLE}} ]</strong></p>
            </div>',
            'subject_template' => 'Subject: {{SUBJECT}}',
            'body_template' => '<p>Dear Sir,</p>
                <p>{{CUSTOM_TEXT}}</p>
                {{CHEQUE_TABLE}}
                <p><strong>In Word: {{TOTAL_AMOUNT_WORDS}}</strong></p>
                <p style="text-align:right;"><strong>Total: {{TOTAL_AMOUNT}}</strong></p>',
            'footer' => '<div style="margin-top:50px;">
                <p>Date: {{DOCUMENT_DATE}}</p>
                <br><br>
                <p>_________________________</p>
                <p><strong>{{SIGNATURE_NAME}}</strong></p>
                <p>{{SIGNATURE_TITLE}}</p>
            </div>',
        ]);

        Template::create([
            'name' => 'Bank Cheque Clearance Letter',
            'type' => 'bank_document',
            'title' => 'BANK CHEQUE CLEARANCE REQUEST',
            'header' => '<div style="text-align:center; margin-bottom:20px;">
                <p>{{COMPANY_ADDRESS}}</p>
            </div>',
            'subject_template' => 'Subject: Request for clearance of cheques issued from {{BANK_NAME}}',
            'body_template' => '<p>Dear Sir,</p>
                <p>{{CUSTOM_TEXT}}</p>
                {{CHEQUE_TABLE}}
                <p><strong>In Word: {{TOTAL_AMOUNT_WORDS}}</strong></p>
                <p style="text-align:right;"><strong>Total: {{TOTAL_AMOUNT}}</strong></p>',
            'footer' => '<div style="margin-top:50px;">
                <p>_________________________</p>
                <p><strong>{{SIGNATURE_NAME}}</strong></p>
                <p>{{SIGNATURE_TITLE}}</p>
            </div>',
        ]);
    }
}
