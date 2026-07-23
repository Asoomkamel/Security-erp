<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جدول تعيين الحراس على المواقع (من/إلى تاريخ) مع نوع الشفت
     * shift: morning | evening | night | full_day
     */
    public function up(): void
    {
        Schema::create('employee_site_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->enum('shift', ['morning', 'evening', 'night', 'full_day'])->default('full_day');
            $table->date('start_date');
            $table->date('end_date')->nullable(); // فارغ = مستمر حاليًا
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_site_assignments');
    }
};
