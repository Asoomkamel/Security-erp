@extends('layouts.app')
@section('title', 'ملخص الرواتب')

@section('content')
    <h4 class="mb-4">ملخص الرواتب الشهري</h4>

    <form class="row g-2 mb-4" method="GET">
        <div class="col-auto">
            <select name="month" class="form-select">
                @for ($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($m == $month)>{{ $m }}</option>
                @endfor
            </select>
        </div>
        <div class="col-auto"><input type="number" name="year" value="{{ $year }}" class="form-control" style="width:100px"></div>
        <div class="col-auto"><button class="btn btn-primary">عرض</button></div>
    </form>

    @if (!$data)
        <div class="alert alert-secondary">لا يوجد تشغيل رواتب لهذا الشهر بعد.</div>
    @else
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card p-3"><div class="text-muted small">إجمالي الأساسي</div><div class="fw-bold">{{ number_format($data['total_base'], 2) }}</div></div></div>
            <div class="col-md-3"><div class="card p-3"><div class="text-muted small">إجمالي البدلات</div><div class="fw-bold">{{ number_format($data['total_allowances'], 2) }}</div></div></div>
            <div class="col-md-3"><div class="card p-3"><div class="text-muted small">خصومات السلف</div><div class="fw-bold">{{ number_format($data['total_advance_deductions'], 2) }}</div></div></div>
            <div class="col-md-3"><div class="card p-3"><div class="text-muted small">الصافي الإجمالي</div><div class="fw-bold">{{ number_format($data['total_net'], 2) }}</div></div></div>
        </div>

        <p>حالة التشغيل: <b>{{ $data['run']->status }}</b> — مدفوع {{ $data['paid_count'] }} من {{ $data['paid_count'] + $data['unpaid_count'] }}</p>

        <table class="table table-bordered bg-white">
            <thead><tr><th>الموظف</th><th>الأساسي</th><th>البدلات</th><th>خصم سلفة</th><th>خصومات أخرى</th><th>الصافي</th><th>الحالة</th></tr></thead>
            <tbody>
                @foreach ($data['run']->items as $item)
                    <tr>
                        <td>{{ $item->employee->full_name }}</td>
                        <td>{{ number_format($item->base_salary, 2) }}</td>
                        <td>{{ number_format($item->allowances, 2) }}</td>
                        <td>{{ number_format($item->advance_deduction, 2) }}</td>
                        <td>{{ number_format($item->other_deductions, 2) }}</td>
                        <td>{{ number_format($item->net_salary, 2) }}</td>
                        <td>{{ $item->is_paid ? '✅ مدفوع' : '⏳ لم يُصرف' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
