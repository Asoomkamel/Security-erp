<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case HR = 'hr';
    case Accountant = 'accountant';
    case Manager = 'manager';
    case Supervisor = 'supervisor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'مدير النظام',
            self::HR => 'الموارد البشرية',
            self::Accountant => 'محاسب',
            self::Manager => 'مدير',
            self::Supervisor => 'مشرف',
        };
    }

    /** كل قيم الأدوار كمصفوفة نصوص (يُستخدم للتحقق من الصلاحيات في Middleware) */
    public static function all(): array
    {
        return array_map(fn(self $role) => $role->value, self::cases());
    }

    /** خيارات جاهزة لعناصر <select> بالواجهة: [value => label] */
    public static function selectOptions(): array
    {
        return collect(self::cases())->mapWithKeys(fn(self $role) => [$role->value => $role->label()])->toArray();
    }
}
