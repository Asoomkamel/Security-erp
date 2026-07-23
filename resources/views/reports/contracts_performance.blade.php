@extends('layouts.app')
@section('title', 'أداء العقود')

@section('content')
    <h4 class="mb-4">تقرير أداء العقود</h4>

    <div class="alert alert-success">إجمالي الإيراد الشهري المتوقع من العقود النشطة: <b>{{ number_format($total_monthly_revenue, 2) }} ر.س</b></div>

    <table class="table table-bordered bg-white mb-4">
        <thead><tr><th>رقم العقد</th><th>العميل</th><th>النوع</th><th>دورة الفوترة</th><th>القيمة الشهرية</th><th>عدد الحراس</th><th>تاريخ الانتهاء</th></tr></thead>
        <tbody>
            @foreach ($active_contracts as $c)
                <tr>
                    <td>{{ $c['contract_number'] }}</td>
                    <td>{{ $c['client'] }}</td>
                    <td>{{ $c['type'] }}</td>
                    <td>{{ $c['billing_cycle'] }}</td>
                    <td>{{ number_format($c['monthly_value'], 2) }}</td>
                    <td>{{ $c['guards_count'] }}</td>
                    <td>{{ $c['end_date'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($expiring_soon->isNotEmpty())
        <div class="card p-3">
            <h6 class="text-warning">⚠️ عقود قريبة من الانتهاء خلال 30 يوم</h6>
            <ul class="mb-0">
                @foreach ($expiring_soon as $c)
                    <li>{{ $c->contract_number }} — {{ $c->clientCompany->name }} — ينتهي في {{ $c->end_date->toDateString() }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection
