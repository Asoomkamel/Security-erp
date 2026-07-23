<?php

namespace App\Console\Commands;

use App\Models\Contract;
use Illuminate\Console\Command;

class RefreshContractsStatus extends Command
{
    protected $signature = 'contracts:refresh-status';

    protected $description = 'تحديث حالات العقود تلقائيًا (تفعيل المسودات المستحقة، وانتهاء العقود المنتهية) + تنبيه بالعقود القريبة من الانتهاء';

    public function handle(): int
    {
        $contracts = Contract::whereIn('status', ['draft', 'active'])->get();

        $expiredCount = 0;
        $activatedCount = 0;

        foreach ($contracts as $contract) {
            $before = $contract->status;
            $contract->refreshStatus();

            if ($before !== $contract->status) {
                $contract->status === 'expired' ? $expiredCount++ : $activatedCount++;
            }
        }

        $this->info("تم تفعيل {$activatedCount} عقد، وإنهاء {$expiredCount} عقد منتهي.");

        // عرض العقود القريبة من الانتهاء خلال 30 يوم (يمكن لاحقًا ربطها بإشعار بريد/واتساب)
        $expiringSoon = Contract::expiringSoon(30)->with('clientCompany')->get();

        if ($expiringSoon->isNotEmpty()) {
            $this->warn("عقود على وشك الانتهاء خلال 30 يوم ({$expiringSoon->count()}):");
            foreach ($expiringSoon as $c) {
                $this->line("- عقد رقم {$c->contract_number} / {$c->clientCompany->name} - ينتهي في {$c->end_date->toDateString()}");
            }
        }

        return self::SUCCESS;
    }
}
