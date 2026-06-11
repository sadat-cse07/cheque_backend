<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheque_voucher', function (Blueprint $table) {
            $table->foreignId('cheque_id')->constrained()->onDelete('cascade');
            $table->foreignId('voucher_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->primary(['cheque_id', 'voucher_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheque_voucher');
    }
};
