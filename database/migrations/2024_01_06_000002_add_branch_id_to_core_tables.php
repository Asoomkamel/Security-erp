<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ربط الجداول الرئيسية بالفرع (branch_id) — nullable = "كل الفروع" لمن يملك صلاحية شاملة
     * مطابق لفكرة branchId في المشروع الآخر، لكن مضاف كطبقة فوق نظامنا العامل فعليًا
     */
    public function up(): void
    {
        $tables = ['users', 'employees', 'client_companies', 'contracts', 'cash_accounts', 'suppliers'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        $tables = ['users', 'employees', 'client_companies', 'contracts', 'cash_accounts', 'suppliers'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('branch_id');
            });
        }
    }
};
