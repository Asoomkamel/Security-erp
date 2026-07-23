<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Site;
use App\Services\GeoService;
use Illuminate\Http\Request;

class AttendanceApiController extends Controller
{
    /** حضور اليوم الحالي للمستخدم المصادَق عليه (إن كان موظفًا مرتبطًا بحساب) */
    public function today(Request $request)
    {
        $employee = $this->resolveEmployee($request);

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        return response()->json(['data' => $attendance]);
    }

    /** تسجيل حضور مع التقاط الإحداثيات والتحقق من النطاق الجغرافي للموقع تلقائيًا */
    public function checkIn(Request $request)
    {
        $employee = $this->resolveEmployee($request);

        $data = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'shift' => 'required|in:morning,evening,night,full_day',
        ]);

        $duplicate = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', now()->toDateString())
            ->where('shift', $data['shift'])
            ->exists();

        if ($duplicate) {
            return response()->json(['message' => 'تم تسجيل الحضور لهذا اليوم والشفت مسبقًا.'], 409);
        }

        $site = Site::findOrFail($data['site_id']);

        $attendance = new Attendance([
            'employee_id' => $employee->id,
            'site_id' => $site->id,
            'date' => now()->toDateString(),
            'check_in' => now()->format('H:i'),
            'check_in_lat' => $data['latitude'],
            'check_in_lng' => $data['longitude'],
            'status' => 'present',
            'shift' => $data['shift'],
        ]);

        if ($site->hasGeofence()) {
            $distance = GeoService::distanceMeters(
                (float) $site->latitude, (float) $site->longitude,
                $data['latitude'], $data['longitude']
            );

            $attendance->check_in_distance_meters = $distance;
            $attendance->is_within_geofence = $distance <= $site->geofence_radius_meters;
        }

        $attendance->save();

        return response()->json(['data' => $attendance], 201);
    }

    /** تسجيل انصراف: يتحقق أن السجل يخص نفس الموظف قبل التحديث */
    public function checkOut(Request $request, Attendance $attendance)
    {
        $employee = $this->resolveEmployee($request);

        if ($attendance->employee_id !== $employee->id) {
            return response()->json(['message' => 'لا يمكنك تعديل حضور موظف آخر.'], 403);
        }

        $data = $request->validate([
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'overtime_hours' => 'nullable|numeric|min:0',
        ]);

        $attendance->update([
            'check_out' => now()->format('H:i'),
            'check_out_lat' => $data['latitude'] ?? null,
            'check_out_lng' => $data['longitude'] ?? null,
            'overtime_hours' => $data['overtime_hours'] ?? $attendance->overtime_hours,
        ]);

        return response()->json(['data' => $attendance->fresh()]);
    }

    /** سجل حضور الموظف السابق (صفحات من 30) */
    public function history(Request $request)
    {
        $employee = $this->resolveEmployee($request);

        $history = Attendance::where('employee_id', $employee->id)
            ->with('site')
            ->latest('date')
            ->paginate(30);

        return response()->json($history);
    }

    private function resolveEmployee(Request $request)
    {
        $employee = $request->user()->employee;

        abort_if(!$employee, 422, 'هذا الحساب غير مرتبط بملف موظف.');

        return $employee;
    }
}
