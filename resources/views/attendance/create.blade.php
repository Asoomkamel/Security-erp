@extends('layouts.app')
@section('title', 'تسجيل حضور (بالموقع الجغرافي)')

@section('content')
    <h4 class="mb-4">تسجيل حضور حارس — بالموقع الجغرافي</h4>

    <div class="card p-4" style="max-width:520px">
        <div id="geoStatus" class="alert alert-secondary small">
            جاري تحديد موقعك الحالي...
        </div>

        <form method="POST" action="{{ route('attendance.store') }}" id="attendanceForm">
            @csrf
            <input type="hidden" name="check_in_lat" id="check_in_lat">
            <input type="hidden" name="check_in_lng" id="check_in_lng">
            <input type="hidden" name="check_in" value="{{ now()->format('H:i') }}">

            <div class="mb-3">
                <label class="form-label">الحارس</label>
                <select name="employee_id" class="form-select" required>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">الموقع</label>
                <select name="site_id" class="form-select" required>
                    @foreach ($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">التاريخ</label>
                <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">الشفت</label>
                <select name="shift" class="form-select" required>
                    <option value="morning">صباحي</option>
                    <option value="evening">مسائي</option>
                    <option value="night">ليلي</option>
                    <option value="full_day" selected>يوم كامل</option>
                </select>
            </div>

            <input type="hidden" name="status" value="present">

            <button type="submit" id="submitBtn" class="btn btn-primary w-100" disabled>
                تسجيل الحضور بالموقع الحالي
            </button>
        </form>
    </div>

    <script>
        const statusBox = document.getElementById('geoStatus');
        const submitBtn = document.getElementById('submitBtn');

        if (!navigator.geolocation) {
            statusBox.textContent = 'المتصفح لا يدعم تحديد الموقع الجغرافي على هذا الجهاز.';
            statusBox.className = 'alert alert-danger small';
        } else {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    document.getElementById('check_in_lat').value = position.coords.latitude;
                    document.getElementById('check_in_lng').value = position.coords.longitude;
                    statusBox.textContent = 'تم تحديد موقعك بدقة ' + Math.round(position.coords.accuracy) + ' متر. يمكنك تسجيل الحضور الآن.';
                    statusBox.className = 'alert alert-success small';
                    submitBtn.disabled = false;
                },
                (error) => {
                    statusBox.textContent = 'تعذّر تحديد الموقع: يرجى السماح بالوصول للموقع الجغرافي من إعدادات المتصفح.';
                    statusBox.className = 'alert alert-danger small';
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }
    </script>
@endsection
