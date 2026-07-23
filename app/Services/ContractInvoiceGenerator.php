<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\DB;

class ContractInvoiceGenerator
{
    /**
     * يمر على كل العقود المستحقة للفوترة الآن وينشئ لها فاتورة بيع تلقائيًا،
     * ثم يحدّث تاريخ آخر فوترة وموعد الفوترة القادم في العقد نفسه.
     *
     * يُستدعى من أمر Artisan مجدول يوميًا (invoices:generate-from-contracts)
     *
     * @return SalesInvoice[] الفواتير التي تم إنشاؤها
     */
    public function generateDueInvoices(): array
    {
        $generated = [];

        Contract::dueForInvoicing()->with('contractSites.site')->each(function (Contract $contract) use (&$generated) {
            $generated[] = $this->generateForContract($contract);
        });

        return array_filter($generated);
    }

    public function generateForContract(Contract $contract): ?SalesInvoice
    {
        if ($contract->contractSites->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($contract) {
            $invoice = SalesInvoice::create([
                'client_company_id' => $contract->client_company_id,
                'contract_id' => $contract->id,
                'invoice_number' => SalesInvoice::generateNumber(),
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays($this->paymentTermDays($contract))->toDateString(),
                'source' => 'auto_contract',
                'status' => 'unpaid',
            ]);

            foreach ($contract->contractSites as $contractSite) {
                $invoice->items()->create([
                    'site_id' => $contractSite->site_id,
                    'description' => sprintf(
                        'خدمات حراسة - %s - %s (%d حارس)',
                        $contractSite->site->name,
                        now()->translatedFormat('F Y'),
                        $contractSite->guards_count
                    ),
                    'quantity' => 1,
                    'unit_price' => $contractSite->site_total,
                ]);
            }

            // recalculateTotals تُستدعى تلقائيًا عبر SalesInvoiceItem Model Event عند إنشاء كل بند

            $contract->update([
                'last_invoiced_at' => now()->toDateString(),
                'next_invoice_due_at' => $contract->calculateNextInvoiceDate(now()),
            ]);

            return $invoice->fresh();
        });
    }

    /** استخراج مهلة السداد بالأيام من نص payment_terms إن وُجد، وإلا 15 يوم افتراضيًا */
    private function paymentTermDays(Contract $contract): int
    {
        if ($contract->payment_terms && preg_match('/(\d+)/', $contract->payment_terms, $m)) {
            return (int) $m[1];
        }

        return 15;
    }
}
