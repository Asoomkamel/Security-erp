<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeApiController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::query()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->employee_type, fn($q) => $q->where('employee_type', $request->employee_type))
            ->paginate(30);

        return response()->json($employees);
    }

    public function show(Employee $employee)
    {
        return response()->json(['data' => $employee->load('currentAssignment.site')]);
    }

    public function attendance(Request $request, Employee $employee)
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        $records = $employee->attendances()->forMonth($month, $year)->with('site')->get();

        return response()->json(['data' => $records]);
    }
}
