<?php

namespace App\Console\Commands;

use App\Services\PayrollService;
use Illuminate\Console\Command;

class GenerateMonthlyPayrollRun extends Command
{
    protected $signature = 'payroll:generate-monthly';

    protected $description = 'إنشاء تشغيل رواتب الشهر الحالي تلقائيًا كمسودة (draft) إن لم يكن موجودًا، ليقوم المحاسب بمراجعته واعتماده';

    public function handle(PayrollService $payrollService): int
    {
        $month = now()->month;
        $year = now()->year;

        try {
            $run = $payrollService->createRun($month, $year);
            $this->info("تم إنشاء {$run->label()} كمسودة بـ {$run->items->count()} موظف. يرجى المراجعة والاعتماد.");
        } catch (\RuntimeException $e) {
            $this->warn($e->getMessage());
        }

        return self::SUCCESS;
    }
}
