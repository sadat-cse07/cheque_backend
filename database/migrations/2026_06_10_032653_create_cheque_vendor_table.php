<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheque_vendor', function (Blueprint $table) {
            $table->foreignId('cheque_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->primary(['cheque_id', 'vendor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheque_vendor');
    }
};
