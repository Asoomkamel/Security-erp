<?php

namespace App\Console\Commands;

use App\Services\ContractInvoiceGenerator;
use Illuminate\Console\Command;

class GenerateContractInvoices extends Command
{
    protected $signature = 'invoices:generate-from-contracts';

    protected $description = 'توليد فواتير بيع تلقائيًا لكل العقود المستحقة للفوترة حسب دورة كل عقد';

    public function handle(ContractInvoiceGenerator $generator): int
    {
        $invoices = $generator->generateDueInvoices();

        if (empty($invoices)) {
            $this->info('لا توجد عقود مستحقة للفوترة اليوم.');
            return self::SUCCESS;
        }

        $this->info('تم إنشاء ' . count($invoices) . ' فاتورة:');
        foreach ($invoices as $invoice) {
            $this->line("- {$invoice->invoice_number} | العميل: {$invoice->clientCompany->name} | الإجمالي: {$invoice->total_amount}");
        }

        return self::SUCCESS;
    }
}
