<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('type', 50)->default('letter'); // fund_requisition, bank_document, payment_letter, etc.
            $table->string('title', 500)->nullable();        // e.g., "FUND REQUISITION FORM"
            $table->text('header')->nullable();              // Company address, logo area
            $table->text('subject_template')->nullable();    // Subject with placeholders
            $table->text('body_template');                   // Main body with placeholders
            $table->text('footer')->nullable();              // Signature, date, etc.
            $table->string('paper_size', 20)->default('A4');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
