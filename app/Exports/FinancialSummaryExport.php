<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FinancialSummaryExport implements FromArray, WithHeadings
{
    public function __construct(private array $summary) {}

    public function headings(): array
    {
        return ['البند', 'المبلغ (ر.س)'];
    }

    public function array(): array
    {
        return [
            ['الفترة من', $this->summary['from']],
            ['الفترة إلى', $this->summary['to']],
            ['إجمالي فواتير البيع', $this->summary['sales_invoiced']],
            ['إجمالي المحصَّل من العملاء', $this->summary['sales_collected']],
            ['إجمالي فواتير الشراء', $this->summary['purchase_invoiced']],
            ['مدفوعات للموردين', $this->summary['supplier_payments']],
            ['رواتب مصروفة', $this->summary['salaries_paid']],
            ['مصاريف/تكاليف تشغيلية', $this->summary['other_expenses']],
            ['مدفوعات أخرى', $this->summary['other_payments']],
            ['صافي التدفق النقدي', $this->summary['net_cash_flow']],
            ['إجمالي أرصدة الصناديق والبنوك حاليًا', $this->summary['cash_accounts_balance']],
        ];
    }
}
