<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جدول بنود الرواتب: راتب كل موظف ضمن تشغيل شهري معين
     * net_salary = base_salary + allowances - advance_deduction - other_deductions
     */
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->decimal('base_salary', 10, 2);
            $table->decimal('allowances', 10, 2)->default(0); // بدلات إضافية (مواصلات، سكن...)
            $table->decimal('advance_deduction', 10, 2)->default(0); // خصم قسط السلفة
            $table->decimal('other_deductions', 10, 2)->default(0); // خصومات أخرى (غياب، جزاء...)
            $table->decimal('net_salary', 10, 2); // الصافي المستحق

            $table->boolean('is_paid')->default(false);
            $table->foreignId('payment_voucher_id')->nullable()->constrained('payment_vouchers')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id']); // موظف واحد مرة واحدة بكل تشغيل
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
