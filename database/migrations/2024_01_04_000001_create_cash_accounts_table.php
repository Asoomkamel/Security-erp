<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جدول الصناديق النقدية والحسابات البنكية
     * type: cash (صندوق نقدي) | bank (حساب بنكي)
     * current_balance: يُحدَّث تلقائيًا مع كل سند قبض/صرف مرتبط به
     */
    public function up(): void
    {
        Schema::create('cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // مثال: "الصندوق الرئيسي" / "حساب البنك الأهلي"
            $table->enum('type', ['cash', 'bank'])->default('cash');
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('iban')->nullable();
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->decimal('current_balance', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_accounts');
    }
};
