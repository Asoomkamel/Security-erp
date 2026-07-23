<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| جدولة المهام التلقائية
|--------------------------------------------------------------------------
| يتطلب تشغيل: php artisan schedule:work (أو ربطه بـ cron على السيرفر)
| * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
*/

// تحديث حالات العقود يوميًا الساعة 6 صباحًا (تفعيل/انتهاء + تنبيه بالعقود القريبة من الانتهاء)
Schedule::command('contracts:refresh-status')->dailyAt('06:00');

// توليد فواتير البيع تلقائيًا من العقود المستحقة يوميًا الساعة 6:15 صباحًا (بعد تحديث حالات العقود مباشرة)
Schedule::command('invoices:generate-from-contracts')->dailyAt('06:15');

// إنشاء مسودة تشغيل رواتب الشهر الجديد تلقائيًا في اليوم الأول من كل شهر
Schedule::command('payroll:generate-monthly')->monthlyOn(1, '07:00');

// إرسال تنبيهات العقود القريبة من الانتهاء والفواتير المتأخرة ووثائق الموظفين يوميًا الساعة 8 صباحًا
Schedule::command('alerts:send-expiry')->dailyAt('08:00');

// تنظيف عدادات الأرقام التسلسلية من السنوات السابقة أول كل شهر
Schedule::command('sequences:cleanup')->monthlyOn(1, '03:00');
