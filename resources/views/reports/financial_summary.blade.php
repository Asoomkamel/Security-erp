@extends('layouts.app')
@section('title', 'الملخص المالي')

@section('content')
    <h4 class="mb-4">الملخص المالي ({{ $summary['from'] }} → {{ $summary['to'] }})</h4>

    <form class="row g-2 mb-4" method="GET">
        <div class="col-auto"><input type="date" name="from" value="{{ $summary['from'] }}" class="form-control"></div>
        <div class="col-auto"><input type="date" name="to" value="{{ $summary['to'] }}" class="form-control"></div>
        <div class="col-auto"><button class="btn btn-primary">تحديث</button></div>
        <div class="col-auto">
            <a href="{{ route('reports.financial-summary.pdf', request()->query()) }}" class="btn btn-outline-danger">تصدير PDF</a>
            <a href="{{ route('reports.financial-summary.excel', request()->query()) }}" class="btn btn-outline-success">تصدير Excel</a>
        </div>
    </form>

    <div class="card p-4">
        <table class="table">
            <tbody>
                <tr><td>إجمالي فواتير البيع</td><td class="text-end">{{ number_format($summary['sales_invoiced'], 2) }}</td></tr>
                <tr><td>إجمالي المحصَّل من العملاء</td><td class="text-end text-success">{{ number_format($summary['sales_collected'], 2) }}</td></tr>
                <tr><td>إجمالي فواتير الشراء</td><td class="text-end">{{ number_format($summary['purchase_invoiced'], 2) }}</td></tr>
                <tr><td>مدفوعات للموردين</td><td class="text-end">{{ number_format($summary['supplier_payments'], 2) }}</td></tr>
                <tr><td>رواتب مصروفة</td><td class="text-end">{{ number_format($summary['salaries_paid'], 2) }}</td></tr>
                <tr><td>مصاريف/تكاليف تشغيلية</td><td class="text-end">{{ number_format($summary['other_expenses'], 2) }}</td></tr>
                <tr class="table-light fw-bold"><td>صافي التدفق النقدي</td><td class="text-end">{{ number_format($summary['net_cash_flow'], 2) }}</td></tr>
                <tr><td>إجمالي أرصدة الصناديق والبنوك حاليًا</td><td class="text-end">{{ number_format($summary['cash_accounts_balance'], 2) }}</td></tr>
            </tbody>
        </table>

        @if ($summary['expenses_by_category']->isNotEmpty())
            <h6 class="mt-4">تفصيل المصاريف حسب التصنيف</h6>
            <table class="table table-sm">
                @foreach ($summary['expenses_by_category'] as $category => $amount)
                    <tr><td>{{ $category }}</td><td class="text-end">{{ number_format($amount, 2) }}</td></tr>
                @endforeach
            </table>
        @endif
    </div>
@endsection
