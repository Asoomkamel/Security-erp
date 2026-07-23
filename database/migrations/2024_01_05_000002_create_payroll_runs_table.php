<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جدول تشغيل الرواتب الشهري (Payroll Run)
     * status: draft (قابل للتعديل) | approved (مُعتمد، جاهز للصرف) | paid (تم صرفه بالكامل)
     */
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->integer('month'); // 1-12
            $table->integer('year');
            $table->decimal('total_net_amount', 12, 2)->default(0);
            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['month', 'year']); // تشغيل واحد فقط لكل شهر
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
