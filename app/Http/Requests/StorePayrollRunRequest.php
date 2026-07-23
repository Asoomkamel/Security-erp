<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollRunRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ];
    }

    public function messages(): array
    {
        return [
            'month.required' => 'الشهر مطلوب.',
            'month.between' => 'الشهر يجب أن يكون بين 1 و 12.',
            'year.required' => 'السنة مطلوبة.',
        ];
    }
}
