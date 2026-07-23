<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::query()
            ->when($request->search, fn($q) => $q->where('full_name', 'like', "%{$request->search}%")
                ->orWhere('employee_code', 'like', "%{$request->search}%")
                ->orWhere('national_id', 'like', "%{$request->search}%"))
            ->when($request->type, fn($q) => $q->where('employee_type', $request->type))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $employee = Employee::create($data);

        return redirect()->route('employees.show', $employee)
            ->with('success', 'تم إضافة الموظف بنجاح.');
    }

    public function show(Employee $employee)
    {
        $employee->load('currentAssignment.site.clientCompany');
        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $this->validateData($request, $employee->id);
        $employee->update($data);

        return redirect()->route('employees.show', $employee)
            ->with('success', 'تم تحديث بيانات الموظف.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete(); // Soft delete
        return redirect()->route('employees.index')->with('success', 'تم حذف الموظف.');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'employee_code' => 'required|string|unique:employees,employee_code,' . $ignoreId,
            'full_name' => 'required|string|max:255',
            'national_id' => 'required|string|unique:employees,national_id,' . $ignoreId,
            'phone' => 'required|string|max:20',
            'phone_alt' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'hire_date' => 'required|date',
            'employee_type' => 'required|in:guard,admin_staff',
            'job_title' => 'nullable|string|max:255',
            'status' => 'required|in:active,on_leave,terminated',
            'base_salary' => 'required|numeric|min:0',
            'id_expiry_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
    }
}
