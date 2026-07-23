<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NumberSequence extends Model
{
    protected $fillable = ['type', 'prefix', 'last_number', 'padding'];

    /**
     * توليد الرقم التالي بأمان تحت أي تزامن (قفل الصف أثناء الزيادة) — بصيغة PREFIX-0001
     * يُنشئ العداد تلقائيًا بأول استخدام إن لم يكن موجودًا
     */
    public static function next(string $type, string $prefix, int $padding = 4): string
    {
        return DB::transaction(function () use ($type, $prefix, $padding) {
            $sequence = static::lockForUpdate()->firstOrCreate(
                ['type' => $type],
                ['prefix' => $prefix, 'last_number' => 0, 'padding' => $padding]
            );

            if (!str_starts_with($prefix, $sequence->prefix)) {
                throw new \RuntimeException("Prefix mismatch for '{$type}': expected '{$sequence->prefix}', got '{$prefix}'");
            }

            $sequence->increment('last_number');

            return sprintf('%s-%s', $sequence->prefix, str_pad((string) $sequence->last_number, $sequence->padding, '0', STR_PAD_LEFT));
        });
    }
}
