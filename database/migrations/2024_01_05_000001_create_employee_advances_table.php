<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جدول سلف الموظفين (Advances)
     * status: active (لا تزال تُخصم شهريًا) | settled (تم سدادها بالكامل)
     */
    public function up(): void
    {
        Schema::create('employee_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->decimal('amount', 10, 2); // مبلغ السلفة الأصلي
            $table->date('given_date');
            $table->decimal('monthly_deduction', 10, 2); // القسط الشهري المخصوم من الراتب
            $table->decimal('remaining_balance', 10, 2); // المتبقي حتى الآن

            $table->enum('status', ['active', 'settled'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_advances');
    }
};
