<?php

namespace App\Console\Commands;

use App\Models\NumberSequence;
use Illuminate\Console\Command;

class CleanupNumberSequences extends Command
{
    protected $signature = 'sequences:cleanup';
    protected $description = 'حذف عدادات الأرقام من السنوات السابقة';

    public function handle(): int
    {
        $currentYear = now()->format('Y');
        $lastYear = now()->subYear()->format('Y');
        $deleted = NumberSequence::query()
            ->where('type', 'not like', "%{$currentYear}")
            ->where('type', 'not like', "%{$lastYear}")
            ->delete();
        $this->info("تم حذف {$deleted} عداد أرقام من سنوات سابقة.");
        return self::SUCCESS;
    }
}
