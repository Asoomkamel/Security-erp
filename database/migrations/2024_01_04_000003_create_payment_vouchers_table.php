<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * سندات الصرف: تسجيل صرف مبالغ (لموردين، رواتب [تُربط في Phase 5]، أو مصاريف عامة)
     * purpose: supplier_payment | salary | expense | other
     */
    public function up(): void
    {
        Schema::create('payment_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_account_id')->constrained('cash_accounts');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('purchase_invoice_id')->nullable()->constrained('purchase_invoices')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete(); // للرواتب لاحقًا Phase 5

            $table->string('voucher_number')->unique();
            $table->date('voucher_date');
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'check', 'pos'])->default('cash');
            $table->enum('purpose', ['supplier_payment', 'salary', 'expense', 'other'])->default('other');
            $table->enum('cost_category', ['rent', 'fuel', 'utilities', 'maintenance', 'transport', 'government_fees', 'other'])
                  ->nullable(); // يُستخدم فقط عندما purpose = expense (لتصنيف التكاليف التشغيلية في التقارير)
            $table->string('paid_to')->nullable(); // اسم المستفيد إن لم يكن مورد/موظف مسجل
            $table->string('reference_number')->nullable();

            $table->enum('status', ['confirmed', 'cancelled'])->default('confirmed');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_vouchers');
    }
};
