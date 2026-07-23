<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClientStatementExport implements FromCollection, WithHeadings
{
    public function __construct(private \Illuminate\Support\Collection $rows) {}

    public function headings(): array
    {
        return ['التاريخ', 'النوع', 'المرجع', 'مدين (مستحق)', 'دائن (محصَّل)', 'الرصيد التراكمي'];
    }

    public function collection()
    {
        return $this->rows->map(fn($r) => [
            $r['date'] instanceof \Carbon\Carbon ? $r['date']->toDateString() : $r['date'],
            $r['type'] === 'invoice' ? 'فاتورة' : 'تحصيل',
            $r['reference'],
            $r['debit'],
            $r['credit'],
            $r['running_balance'],
        ]);
    }
}
