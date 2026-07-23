<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesInvoiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'client_company_id' => 'required|exists:client_companies,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.site_id' => 'nullable|exists:sites,id',
        ];
    }

    public function messages(): array
    {
        return [
            'client_company_id.required' => 'يرجى اختيار الشركة العميلة.',
            'invoice_date.required' => 'تاريخ الفاتورة مطلوب.',
            'items.required' => 'يجب إضافة بند واحد على الأقل.',
            'items.*.description.required' => 'وصف البند مطلوب.',
            'items.*.quantity.min' => 'الكمية يجب أن تكون 1 على الأقل.',
        ];
    }
}
