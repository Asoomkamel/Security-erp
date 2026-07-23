<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceRequest;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Site;
use App\Services\GeoService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $attendances = Attendance::query()
            ->with('employee', 'site')
            ->when($request->employee_id, fn($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->date, fn($q) => $q->whereDate('date', $request->date))
            ->when($request->month && $request->year, fn($q) => $q->forMonth((int) $request->month, (int) $request->year))
            ->latest('date')
            ->paginate(30);

        return view('attendance.index', compact('attendances'));
    }

    /** نموذج تسجيل حضور من الجوال (يلتقط الموقع الجغرافي تلقائيًا عبر متصفح الجوال قبل الإرسال) */
    public function create()
    {
        $employees = Employee::where('status', 'active')->where('employee_type', 'guard')->get();
        $sites = Site::where('is_active', true)->get();

        return view('attendance.create', compact('employees', 'sites'));
    }

    /** تسجيل حضور/انصراف يوم واحد لموظف (من المشرف الميداني)، مع تحقق جغرافي اختياري */
    public function store(StoreAttendanceRequest $request)
    {
        $data = $request->validated();

        // تحقق جغرافي: إن كان الموقع مسجَّل بإحداثيات، وتوفرت إحداثيات الحضور الفعلية، نحسب المسافة تلقائيًا
        if (!empty($data['site_id']) && !empty($data['check_in_lat']) && !empty($data['check_in_lng'])) {
            $site = Site::find($data['site_id']);

            if ($site && $site->hasGeofence()) {
                $distance = GeoService::distanceMeters(
                    (float) $site->latitude, (float) $site->longitude,
                    (float) $data['check_in_lat'], (float) $data['check_in_lng']
                );

                $data['check_in_distance_meters'] = $distance;
                $data['is_within_geofence'] = $distance <= $site->geofence_radius_meters;
            }
        }

        $attendance = Attendance::updateOrCreate(
            ['employee_id' => $data['employee_id'], 'date' => $data['date'], 'shift' => $data['shift']],
            $data
        );

        $geoNote = '';
        if (!is_null($attendance->is_within_geofence)) {
            $geoNote = $attendance->is_within_geofence
                ? " (ضمن نطاق الموقع - {$attendance->check_in_distance_meters} م)"
                : " ⚠️ (خارج نطاق الموقع بمسافة {$attendance->check_in_distance_meters} م)";
        }

        return back()->with('success', "تم تسجيل حضور {$attendance->employee->full_name} ليوم {$data['date']}{$geoNote}.");
    }

    /** ملخص شهري لموظف: أيام حضور/غياب/تأخير وساعات إضافية (يُستخدم أيضًا داخل تشغيل الرواتب) */
    public function monthlySummary(Request $request, Employee $employee)
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        $records = $employee->attendances()->forMonth($month, $year)->get();

        $summary = [
            'present' => $records->where('status', 'present')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'late' => $records->where('status', 'late')->count(),
            'leave' => $records->where('status', 'leave')->count(),
            'overtime_hours' => $records->sum('overtime_hours'),
            'outside_geofence' => $records->where('is_within_geofence', false)->count(),
        ];

        return view('attendance.monthly_summary', compact('employee', 'summary', 'month', 'year'));
    }
}
