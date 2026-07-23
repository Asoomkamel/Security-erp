@extends('layouts.app')
@section('title', 'سجلات الحضور والانصراف')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>سجلات الحضور والانصراف</h4>
        <a href="{{ route('attendance.create') }}" class="btn btn-primary btn-sm">+ تسجيل حضور بالموقع الجغرافي</a>
    </div>

    <table class="table table-bordered bg-white">
        <thead>
            <tr>
                <th>التاريخ</th><th>الموظف</th><th>الموقع</th><th>الشفت</th><th>الحالة</th>
                <th>دخول</th><th>خروج</th><th>إضافي (ساعة)</th><th>الموقع الجغرافي</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($attendances as $a)
                <tr>
                    <td>{{ $a->date->toDateString() }}</td>
                    <td>{{ $a->employee->full_name }}</td>
                    <td>{{ $a->site->name ?? '-' }}</td>
                    <td>{{ $a->shift }}</td>
                    <td>{{ $a->status }}</td>
                    <td>{{ $a->check_in }}</td>
                    <td>{{ $a->check_out }}</td>
                    <td>{{ $a->overtime_hours }}</td>
                    <td>
                        @if (is_null($a->is_within_geofence))
                            <span class="text-muted">—</span>
                        @elseif ($a->is_within_geofence)
                            <span class="text-success">✅ ضمن النطاق ({{ $a->check_in_distance_meters }} م)</span>
                        @else
                            <span class="text-danger">⚠️ خارج النطاق ({{ $a->check_in_distance_meters }} م)</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $attendances->links() }}
@endsection
