<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جدول المواقع (الموقع الذي يتم توفير الحراسة له - تابع لشركة عميلة)
     * مثال: فرع الرياض - المستودع الرئيسي - إلخ
     */
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_company_id')->constrained('client_companies')->cascadeOnDelete();
            $table->string('name'); // اسم الموقع
            $table->text('address')->nullable();
            $table->string('site_manager_name')->nullable(); // مسؤول التواصل بالموقع
            $table->string('site_manager_phone')->nullable();
            $table->integer('required_guards_count')->default(1); // عدد الحراس المطلوب بالموقع
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
