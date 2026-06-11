<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->constrained()->onDelete('cascade');
            $table->string('cheque_number', 50);
            $table->date('cheque_date');
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->string('amount_in_words', 500);
            $table->string('status', 20)->default('active');    // active, voided
            $table->timestamp('printed_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->unique(['bank_id', 'cheque_number']);
            $table->index('cheque_date');
            $table->index('vendor_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};
