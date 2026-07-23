<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جدول عقود الحراسة مع الشركات العميلة
     *
     * contract_type:
     *   - monthly  : عقد بفوترة شهرية متجددة (مدة غير محددة أو طويلة)
     *   - annual   : عقد بمدة سنة، بفوترة شهرية أو ربع سنوية أو دفعة واحدة (billing_cycle يحدد ذلك)
     *   - lump_sum : عقد مقطوع بمبلغ إجمالي وفوترة لمرة واحدة (أو دفعات محددة يدويًا)
     *
     * billing_cycle: كيف تُصدر الفواتير من هذا العقد
     *   - monthly | quarterly | annual | one_time
     *
     * status: draft (تحت الإعداد) | active | expiring_soon (تُحسب تلقائيًا) | expired | cancelled
     */
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_company_id')->constrained('client_companies')->cascadeOnDelete();

            $table->string('contract_number')->unique(); // رقم العقد المرجعي
            $table->enum('contract_type', ['monthly', 'annual', 'lump_sum'])->default('monthly');
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'annual', 'one_time'])->default('monthly');

            $table->date('start_date');
            $table->date('end_date')->nullable(); // فارغ = عقد مفتوح المدة (شهري مستمر)

            // القيمة الإجمالية للعقد (تُستخدم أساسًا في lump_sum و annual)
            $table->decimal('total_value', 12, 2)->nullable();

            // القيمة الدورية التي تُصدر بها الفاتورة (تُستخدم أساسًا في monthly)
            $table->decimal('recurring_amount', 12, 2)->nullable();

            $table->boolean('auto_renew')->default(false);
            $table->enum('status', ['draft', 'active', 'expired', 'cancelled'])->default('draft');

            $table->date('last_invoiced_at')->nullable(); // آخر مرة صدرت فيها فاتورة من هذا العقد (لمنع التكرار)
            $table->date('next_invoice_due_at')->nullable(); // موعد استحقاق الفاتورة القادمة

            $table->string('payment_terms')->nullable(); // مثال: "الدفع خلال 15 يوم من الفاتورة"
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable(); // مرفق نسخة العقد (PDF)

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
