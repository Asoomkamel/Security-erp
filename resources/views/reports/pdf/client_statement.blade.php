<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        td, th { border: 1px solid #ccc; padding: 5px 8px; text-align: right; }
        h2 { text-align: center; }
    </style>
</head>
<body>
    <h2>كشف حساب: {{ $company->name }}</h2>
    <p>إجمالي المستحق حاليًا: <b>{{ number_format($total_due, 2) }} ر.س</b></p>

    <table>
        <tr><th>التاريخ</th><th>النوع</th><th>المرجع</th><th>مدين</th><th>دائن</th><th>الرصيد</th></tr>
        @foreach ($rows as $row)
            <tr>
                <td>{{ optional($row['date'])->toDateString() ?? $row['date'] }}</td>
                <td>{{ $row['type'] === 'invoice' ? 'فاتورة' : 'تحصيل' }}</td>
                <td>{{ $row['reference'] }}</td>
                <td>{{ $row['debit'] ? number_format($row['debit'], 2) : '-' }}</td>
                <td>{{ $row['credit'] ? number_format($row['credit'], 2) : '-' }}</td>
                <td>{{ number_format($row['running_balance'], 2) }}</td>
            </tr>
        @endforeach
    </table>
</body>
</html>
