<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeAdvance;
use Illuminate\Http\Request;

class EmployeeAdvanceController extends Controller
{
    public function index(Request $request)
    {
        $advances = EmployeeAdvance::query()
            ->with('employee')
            ->when($request->employee_id, fn($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return view('advances.index', compact('advances'));
    }

    public function create()
    {
        $employees = Employee::where('status', 'active')->get();
        return view('advances.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric|min:1',
            'given_date' => 'required|date',
            'monthly_deduction' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
        ]);

        // منع منح أكثر من سلفة نشطة واحدة لنفس الموظف في نفس الوقت (تبسيطًا لمنطق الخصم الشهري)
        $hasActive = Employee::findOrFail($data['employee_id'])->activeAdvances()->exists();
        if ($hasActive) {
            return back()->withInput()->withErrors([
                'employee_id' => 'هذا الموظف لديه سلفة نشطة لم تُسدَّد بعد.',
            ]);
        }

        $advance = EmployeeAdvance::create(['status' => 'active', ...$data]);

        return redirect()->route('advances.show', $advance)->with('success', 'تم تسجيل السلفة بنجاح.');
    }

    public function show(EmployeeAdvance $advance)
    {
        $advance->load('employee');
        return view('advances.show', compact('advance'));
    }
}
