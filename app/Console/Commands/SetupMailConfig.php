<?php

namespace App\Console\Commands;

use App\Models\SystemSetting;
use Illuminate\Console\Command;

class SetupMailConfig extends Command
{
    protected $signature = 'setup:mail {driver=smtp}';
    protected $description = 'إعداد بيانات البريد الإلكتروني تفاعليًا وحفظها في الإعدادات وملف .env';

    public function handle(): int
    {
        $driver = $this->argument('driver');

        $host = $this->ask('عنوان سيرفر البريد (Host)');
        $port = $this->ask('المنفذ (Port)', '587');
        $username = $this->ask('اسم المستخدم (Username)');
        $password = $this->secret('كلمة المرور (Password)');
        $encryption = $this->choice('نوع التشفير', ['tls', 'ssl', 'none'], 0);
        $fromAddress = $this->ask('البريد المُرسِل منه (From Address)');
        $fromName = $this->ask('اسم المُرسِل (From Name)', config('app.name'));

        $settings = [
            'mail_driver' => $driver,
            'mail_host' => $host,
            'mail_port' => $port,
            'mail_username' => $username,
            'mail_password' => $password,
            'mail_encryption' => $encryption === 'none' ? '' : $encryption,
            'mail_from_address' => $fromAddress,
            'mail_from_name' => $fromName,
        ];

        foreach ($settings as $key => $value) {
            SystemSetting::set($key, $value);
        }

        $this->updateEnvFile([
            'MAIL_MAILER' => $driver,
            'MAIL_HOST' => $host,
            'MAIL_PORT' => $port,
            'MAIL_USERNAME' => $username,
            'MAIL_PASSWORD' => $password,
            'MAIL_ENCRYPTION' => $encryption === 'none' ? 'null' : $encryption,
            'MAIL_FROM_ADDRESS' => $fromAddress,
            'MAIL_FROM_NAME' => '"' . $fromName . '"',
        ]);

        $this->info('✅ تم حفظ إعدادات البريد بنجاح في .env وقاعدة البيانات.');
        $this->line('للتحقق من عمل الإرسال فعليًا، شغّل: php artisan notifications:test your@email.com');

        return self::SUCCESS;
    }

    /** تحديث ملف .env بالقيم الجديدة دون حذف بقية المتغيرات (يضيف السطر إن لم يكن موجودًا) */
    private function updateEnvFile(array $values): void
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            $this->warn('ملف .env غير موجود، تم تخطي تحديثه (الإعدادات محفوظة بقاعدة البيانات فقط).');
            return;
        }

        $content = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $line = "{$key}={$value}";

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $line, $content);
            } else {
                $content .= PHP_EOL . $line;
            }
        }

        file_put_contents($envPath, $content);
    }
}
