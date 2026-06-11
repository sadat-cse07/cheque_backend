<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained()->onDelete('cascade');
            $table->string('reference_no', 100)->unique();
            $table->string('title', 500)->nullable();
            $table->date('document_date');
            $table->json('cheque_ids')->nullable();          // Selected cheque IDs
            $table->json('voucher_ids')->nullable();         // Selected voucher IDs
            $table->json('custom_fields')->nullable();       // Any extra data
            $table->text('final_content')->nullable();       // Rendered HTML
            $table->string('status', 20)->default('draft');  // draft, generated, printed
            $table->timestamp('printed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_documents');
    }
};
