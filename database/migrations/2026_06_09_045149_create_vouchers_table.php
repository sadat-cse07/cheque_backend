<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->date('voucher_date');
            $table->text('particulars')->nullable();
            $table->string('voucher_name', 255);
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->boolean('is_paid')->default(false);
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('voucher_date');
            $table->index('is_paid');
            $table->index('voucher_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
