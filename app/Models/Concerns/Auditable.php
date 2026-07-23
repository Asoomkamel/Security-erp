<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;

/**
 * أضف "use Auditable;" لأي نموذج حساس (العقود، الفواتير، السندات، الرواتب...)
 * ليُسجَّل كل إنشاء/تعديل/حذف تلقائيًا بسجل التدقيق العام دون أي كود إضافي بالـ Controller
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn($model) => static::writeAuditLog('created', null, $model->getAttributes()));
        static::updated(fn($model) => static::writeAuditLog('updated', $model->getOriginal(), $model->getChanges()));
        static::deleted(fn($model) => static::writeAuditLog('deleted', $model->getAttributes(), null));
    }

    protected static function writeAuditLog(string $action, ?array $old, ?array $new): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => static::class,
            'auditable_id' => $new['id'] ?? $old['id'] ?? 0,
            'old_data' => $old,
            'new_data' => $new,
            'ip_address' => request()?->ip(),
        ]);
    }
}
