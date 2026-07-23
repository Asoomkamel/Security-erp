<?php

namespace App\Http\Controllers;

use App\Models\EmployeeSiteAssignment;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    /**
     * تعيين حارس على موقع (ينهي أي تعيين نشط سابق له تلقائيًا)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'site_id' => 'required|exists:sites,id',
            'shift' => 'required|in:morning,evening,night,full_day',
            'start_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        // إنهاء أي تعيين نشط حالي لنفس الحارس قبل التعيين الجديد
        EmployeeSiteAssignment::where('employee_id', $data['employee_id'])
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'end_date' => now()->toDateString(),
            ]);

        $data['is_active'] = true;
        EmployeeSiteAssignment::create($data);

        return back()->with('success', 'تم تعيين الحارس على الموقع بنجاح.');
    }

    /**
     * إنهاء تعيين حارس من موقع (بدون تعيين جديد)
     */
    public function end(EmployeeSiteAssignment $assignment)
    {
        $assignment->update([
            'is_active' => false,
            'end_date' => now()->toDateString(),
        ]);

        return back()->with('success', 'تم إنهاء تعيين الحارس من الموقع.');
    }
}
