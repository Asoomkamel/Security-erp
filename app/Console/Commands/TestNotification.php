<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Contract;
use App\Models\User;
use App\Notifications\ContractExpiryNotification;
use Illuminate\Console\Command;

class TestNotification extends Command
{
    protected $signature = 'notifications:test {email?}';
    protected $description = 'إرسال إشعار تجريبي (اقتراب انتهاء عقد) للتأكد من صحة إعدادات البريد';

    public function handle(): int
    {
        $email = $this->argument('email') ?? User::where('role', UserRole::Admin)->value('email');

        if (!$email) {
            $this->error('لا يوجد بريد إلكتروني لإرسال الإشعار إليه (لا يوجد أدمن ولم يُحدَّد بريد يدويًا).');
            return self::FAILURE;
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => 'مستخدم تجريبي', 'password' => bcrypt(str()->random(16)), 'role' => UserRole::Admin, 'is_active' => true]
        );

        $contract = Contract::first();

        if (!$contract) {
            $this->error('لا يوجد أي عقد بقاعدة البيانات لإرسال إشعار تجريبي عنه. أضف عقدًا أولًا.');
            return self::FAILURE;
        }

        try {
            $user->notify(new ContractExpiryNotification($contract, 15));
            $this->info("✅ تم إرسال إشعار تجريبي بنجاح إلى {$email}.");
            $this->line('إن كنت تستخدم Mailpit محليًا، راجع الرسالة على http://localhost:8025');
        } catch (\Throwable $e) {
            $this->error('❌ فشل إرسال الإشعار: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
