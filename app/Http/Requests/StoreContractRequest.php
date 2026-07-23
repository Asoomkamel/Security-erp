<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $contractId = $this->route('contract')?->id;
        return [
            'client_company_id' => 'required|exists:client_companies,id',
            'contract_number' => 'required|string|unique:contracts,contract_number,' . $contractId,
            'contract_type' => 'required|in:monthly,annual,lump_sum',
            'billing_cycle' => 'required|in:monthly,quarterly,annual,one_time',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'total_value' => 'nullable|numeric|min:0',
            'recurring_amount' => 'nullable|numeric|min:0',
            'auto_renew' => 'boolean',
            'status' => 'required|in:draft,active,expired,cancelled',
            'payment_terms' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'sites' => 'required|array|min:1',
            'sites.*.site_id' => 'required|exists:sites,id',
            'sites.*.guards_count' => 'required|integer|min:1',
            'sites.*.unit_price' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'client_company_id.required' => 'يرجى اختيار الشركة العميلة.',
            'contract_number.required' => 'رقم العقد مطلوب.',
            'contract_number.unique' => 'رقم العقد موجود مسبقًا.',
            'contract_type.required' => 'نوع العقد مطلوب.',
            'billing_cycle.required' => 'دورة الفوترة مطلوبة.',
            'start_date.required' => 'تاريخ بداية العقد مطلوب.',
            'end_date.after' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية.',
            'sites.required' => 'يجب إضافة موقع واحد على الأقل للعقد.',
            'sites.*.site_id.required' => 'الموقع مطلوب.',
            'sites.*.guards_count.required' => 'عدد الحراس مطلوب لكل موقع.',
            'sites.*.guards_count.min' => 'عدد الحراس يجب أن يكون 1 على الأقل.',
            'sites.*.unit_price.required' => 'سعر الحارس مطلوب لكل موقع.',
        ];
    }
}
