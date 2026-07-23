@extends('layouts.app')
@section('title', 'الملخص الشهري للحضور')

@section('content')
    <h4 class="mb-4">ملخص حضور {{ $employee->full_name }} — {{ $month }}/{{ $year }}</h4>

    <div class="row g-3">
        <div class="col-md-2"><div class="card p-3 text-center"><div class="text-muted small">حاضر</div><div class="kpi-value text-success">{{ $summary['present'] }}</div></div></div>
        <div class="col-md-2"><div class="card p-3 text-center"><div class="text-muted small">غائب</div><div class="kpi-value text-danger">{{ $summary['absent'] }}</div></div></div>
        <div class="col-md-2"><div class="card p-3 text-center"><div class="text-muted small">متأخر</div><div class="kpi-value text-warning">{{ $summary['late'] }}</div></div></div>
        <div class="col-md-2"><div class="card p-3 text-center"><div class="text-muted small">إجازة</div><div class="kpi-value">{{ $summary['leave'] }}</div></div></div>
        <div class="col-md-2"><div class="card p-3 text-center"><div class="text-muted small">ساعات إضافي</div><div class="kpi-value">{{ $summary['overtime_hours'] }}</div></div></div>
        <div class="col-md-2"><div class="card p-3 text-center"><div class="text-muted small">خارج النطاق</div><div class="kpi-value text-danger">{{ $summary['outside_geofence'] }}</div></div></div>
    </div>
@endsection
