<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * مستفاد من مراجعة نسخة ثانية: فصل الجواز/الإقامة عن بعضهما بتاريخي انتهاء مستقلين،
     * إضافة رقم التأمينات الاجتماعية (GOSI) ورخصة الحراسة الأمنية والبيانات البنكية
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('passport_number')->nullable()->after('national_id');
            $table->date('passport_expiry')->nullable()->after('passport_number');
            $table->string('iqama_number')->nullable()->after('passport_expiry');
            $table->date('iqama_expiry')->nullable()->after('iqama_number');

            $table->string('gosi_number')->nullable()->after('id_expiry_date');
            $table->string('security_license_number')->nullable()->after('gosi_number');
            $table->date('security_license_expiry')->nullable()->after('security_license_number');
            $table->string('medical_insurance_number')->nullable()->after('security_license_expiry');

            $table->string('bank_name')->nullable()->after('base_salary');
            $table->string('bank_account')->nullable()->after('bank_name');
            $table->string('iban')->nullable()->after('bank_account');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'passport_number', 'passport_expiry', 'iqama_number', 'iqama_expiry',
                'gosi_number', 'security_license_number', 'security_license_expiry',
                'medical_insurance_number', 'bank_name', 'bank_account', 'iban',
            ]);
        });
    }
};
