<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\SystemSetting;
use App\Notifications\ContractExpiryNotification;
use App\Notifications\OverdueInvoiceNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendExpiryAlerts extends Command
{
    protected $signature = 'alerts:send-expiry';
    protected $description = 'إرسال تنبيهات انتهاء العقود والهوية والفواتير المتأخرة';

    public function handle(): int
    {
        $contractDays = (int) SystemSetting::get('notification_contract_expiry_days', '30');
        $invoiceDays = (int) SystemSetting::get('notification_overdue_invoice_days', '7');

        $adminsAndManagers = User::whereIn('role', ['admin', 'manager'])->get();
        if ($adminsAndManagers->isEmpty()) {
            $this->warn('لا يوجد مستخدمون لإرسال التنبيهات إليهم.');
            return self::SUCCESS;
        }

        $expiringContracts = Contract::expiringSoon($contractDays)->with('clientCompany')->get();
        foreach ($expiringContracts as $contract) {
            $days = (int) now()->diffInDays($contract->end_date);
            Notification::send($adminsAndManagers, new ContractExpiryNotification($contract, $days));
        }

        $overdueInvoices = SalesInvoice::overdue()->with('clientCompany')->get();
        foreach ($overdueInvoices as $invoice) {
            Notification::send($adminsAndManagers, new OverdueInvoiceNotification($invoice));
        }

        $hrUsers = User::whereIn('role', ['admin', 'hr'])->get();
        $expiringIds = Employee::where('status', 'active')->get()->filter(fn($e) => $e->nearestExpiryAlert((int) SystemSetting::get('notification_id_expiry_days', '30')) !== null);

        $this->info("✅ تم إرسال التنبيهات بنجاح.");
        $this->line("- عقود قريبة من الانتهاء: {$expiringContracts->count()}");
        $this->line("- فواتير متأخرة: {$overdueInvoices->count()}");
        $this->line("- موظفين بقرب انتهاء وثائقهم: {$expiringIds->count()}");

        return self::SUCCESS;
    }
}
