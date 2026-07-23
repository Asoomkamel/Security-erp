<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * مستفاد من مراجعة نسخة ثانية: تفصيل البدلات بدل رقم إجمالي واحد،
     * + ربط حقيقي بالحضور الفعلي لحساب خصم الغياب والعمل الإضافي تلقائيًا
     * (وهذا تحسين لا يوجد في أي من المشروعين، لأن المشروع الآخر يملك الحقول لكن بدون حساب فعلي)
     */
    public function up(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->decimal('housing_allowance', 10, 2)->default(0)->after('allowances');
            $table->decimal('transport_allowance', 10, 2)->default(0)->after('housing_allowance');
            $table->decimal('food_allowance', 10, 2)->default(0)->after('transport_allowance');

            $table->decimal('overtime_hours', 6, 2)->default(0)->after('food_allowance');
            $table->decimal('overtime_amount', 10, 2)->default(0)->after('overtime_hours');
            $table->decimal('bonus', 10, 2)->default(0)->after('overtime_amount');

            $table->decimal('absence_days', 5, 2)->default(0)->after('advance_deduction');
            $table->decimal('absence_deduction', 10, 2)->default(0)->after('absence_days');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropColumn([
                'housing_allowance', 'transport_allowance', 'food_allowance',
                'overtime_hours', 'overtime_amount', 'bonus',
                'absence_days', 'absence_deduction',
            ]);
        });
    }
};
