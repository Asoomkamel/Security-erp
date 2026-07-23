@extends('layouts.app')
@section('title', 'كشف حساب مورد')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>كشف حساب: {{ $supplier->name }}</h4>
        <a href="{{ route('reports.supplier-statement.excel', $supplier) }}" class="btn btn-outline-success btn-sm">تصدير Excel</a>
    </div>

    <div class="alert alert-info">إجمالي المستحق علينا حاليًا: <b>{{ number_format($total_due, 2) }} ر.س</b></div>

    <table class="table table-bordered bg-white">
        <thead><tr><th>التاريخ</th><th>النوع</th><th>المرجع</th><th>مدين (فاتورة)</th><th>دائن (مدفوع)</th><th>الرصيد التراكمي</th></tr></thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ optional($row['date'])->toDateString() ?? $row['date'] }}</td>
                    <td>{{ $row['type'] === 'invoice' ? 'فاتورة شراء' : 'سند صرف' }}</td>
                    <td>{{ $row['reference'] }}</td>
                    <td>{{ $row['debit'] ? number_format($row['debit'], 2) : '-' }}</td>
                    <td>{{ $row['credit'] ? number_format($row['credit'], 2) : '-' }}</td>
                    <td>{{ number_format($row['running_balance'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
