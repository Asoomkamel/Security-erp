<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * الحضور والانصراف اليومي الفعلي — الفجوة الأهم في النسخة الأولى (كانت مجرد "تعيين" طويل الأمد)
     * status: present | absent | late | leave | holiday | excuse
     * لا يمكن تكرار نفس الموظف بنفس اليوم ونفس الوردية (unique)
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();

            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->enum('status', ['present', 'absent', 'late', 'leave', 'holiday', 'excuse'])->default('present');
            $table->enum('shift', ['morning', 'evening', 'night', 'full_day'])->default('full_day');
            $table->decimal('overtime_hours', 5, 2)->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'date', 'shift']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
