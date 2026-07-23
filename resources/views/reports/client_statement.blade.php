@extends('layouts.app')
@section('title', 'كشف حساب عميل')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>كشف حساب: {{ $company->name }}</h4>
        <div>
            <a href="{{ route('reports.client-statement.pdf', $company) }}" class="btn btn-outline-danger btn-sm">تصدير PDF</a>
            <a href="{{ route('reports.client-statement.excel', $company) }}" class="btn btn-outline-success btn-sm">تصدير Excel</a>
        </div>
    </div>

    <div class="alert alert-info">إجمالي المستحق حاليًا: <b>{{ number_format($total_due, 2) }} ر.س</b></div>

    <table class="table table-bordered bg-white">
        <thead><tr><th>التاريخ</th><th>النوع</th><th>المرجع</th><th>مدين (مستحق)</th><th>دائن (محصَّل)</th><th>الرصيد التراكمي</th></tr></thead>
        <tbody>
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
        </tbody>
    </table>
@endsection
