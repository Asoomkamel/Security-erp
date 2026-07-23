<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        td, th { border: 1px solid #ccc; padding: 6px 10px; text-align: right; }
        h2 { text-align: center; }
    </style>
</head>
<body>
    <h2>الملخص المالي</h2>
    <p style="text-align:center">الفترة من {{ $summary['from'] }} إلى {{ $summary['to'] }}</p>

    <table>
        <tr><td>إجمالي فواتير البيع</td><td>{{ number_format($summary['sales_invoiced'], 2) }}</td></tr>
        <tr><td>إجمالي المحصَّل من العملاء</td><td>{{ number_format($summary['sales_collected'], 2) }}</td></tr>
        <tr><td>إجمالي فواتير الشراء</td><td>{{ number_format($summary['purchase_invoiced'], 2) }}</td></tr>
        <tr><td>مدفوعات للموردين</td><td>{{ number_format($summary['supplier_payments'], 2) }}</td></tr>
        <tr><td>رواتب مصروفة</td><td>{{ number_format($summary['salaries_paid'], 2) }}</td></tr>
        <tr><td>مصاريف/تكاليف تشغيلية</td><td>{{ number_format($summary['other_expenses'], 2) }}</td></tr>
        <tr><td><b>صافي التدفق النقدي</b></td><td><b>{{ number_format($summary['net_cash_flow'], 2) }}</b></td></tr>
        <tr><td>إجمالي أرصدة الصناديق والبنوك حاليًا</td><td>{{ number_format($summary['cash_accounts_balance'], 2) }}</td></tr>
    </table>
</body>
</html>
