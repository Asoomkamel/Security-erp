<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'site_id' => 'nullable|exists:sites,id',
            'date' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i|after:check_in',
            'check_in_lat' => 'nullable|numeric|between:-90,90',
            'check_in_lng' => 'nullable|numeric|between:-180,180',
            'check_out_lat' => 'nullable|numeric|between:-90,90',
            'check_out_lng' => 'nullable|numeric|between:-180,180',
            'status' => 'required|in:present,absent,late,leave,holiday,excuse',
            'shift' => 'required|in:morning,evening,night,full_day',
            'overtime_hours' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'الموظف مطلوب.',
            'date.required' => 'التاريخ مطلوب.',
            'status.required' => 'حالة الحضور مطلوبة.',
            'shift.required' => 'الشفت مطلوب.',
            'check_out.after' => 'وقت الانصراف يجب أن يكون بعد وقت الحضور.',
        ];
    }
}
