<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جدول الموظفين (يشمل الحراس والإداريين)
     * employee_type: guard | admin_staff
     * status: active | on_leave | terminated
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('employee_code')->unique(); // كود الموظف الوظيفي
            $table->string('full_name');
            $table->string('national_id')->unique(); // رقم الهوية/الإقامة
            $table->string('phone');
            $table->string('phone_alt')->nullable();
            $table->text('address')->nullable();
            $table->date('birth_date')->nullable();
            $table->date('hire_date');
            $table->date('termination_date')->nullable();

            $table->enum('employee_type', ['guard', 'admin_staff'])->default('guard');
            $table->string('job_title')->nullable(); // حارس / رئيس حراس / محاسب... إلخ
            $table->enum('status', ['active', 'on_leave', 'terminated'])->default('active');

            $table->decimal('base_salary', 10, 2)->default(0);
            $table->string('photo_path')->nullable();
            $table->string('id_document_path')->nullable(); // صورة الهوية/الإقامة
            $table->date('id_expiry_date')->nullable(); // تنبيه انتهاء الإقامة/الهوية

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
