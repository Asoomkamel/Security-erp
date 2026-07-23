<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * سندات القبض: تسجيل استلام مبالغ نقدية (غالبًا من عملاء مقابل فواتير بيع)
     * payment_method: cash | bank_transfer | check | pos
     * status: confirmed | cancelled
     */
    public function up(): void
    {
        Schema::create('receipt_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_account_id')->constrained('cash_accounts');
            $table->foreignId('client_company_id')->nullable()->constrained('client_companies')->nullOnDelete();
            $table->foreignId('sales_invoice_id')->nullable()->constrained('sales_invoices')->nullOnDelete();

            $table->string('voucher_number')->unique();
            $table->date('voucher_date');
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'check', 'pos'])->default('cash');
            $table->string('received_from')->nullable(); // اسم الشخص المستلم منه المبلغ فعليًا
            $table->string('reference_number')->nullable(); // رقم الشيك/التحويل

            $table->enum('status', ['confirmed', 'cancelled'])->default('confirmed');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_vouchers');
    }
};
