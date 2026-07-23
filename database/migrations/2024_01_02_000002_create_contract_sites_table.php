<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جدول ربط العقد بالمواقع المشمولة به (عقد واحد قد يغطي أكثر من موقع لنفس الشركة)
     * كل موقع له عدد حراس وسعر خاص به ضمن هذا العقد
     */
    public function up(): void
    {
        Schema::create('contract_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();

            $table->integer('guards_count')->default(1); // عدد الحراس المتعاقد عليهم لهذا الموقع
            $table->decimal('unit_price', 10, 2); // سعر الحارس الواحد بالدورة (شهري غالبًا)
            $table->decimal('site_total', 12, 2); // guards_count * unit_price (تُحسب وتُخزّن)

            $table->timestamps();

            $table->unique(['contract_id', 'site_id']); // نفس الموقع لا يتكرر بنفس العقد
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_sites');
    }
};
