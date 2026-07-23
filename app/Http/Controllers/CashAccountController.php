<?php

namespace App\Http\Controllers;

use App\Models\CashAccount;
use Illuminate\Http\Request;

class CashAccountController extends Controller
{
    public function index()
    {
        $accounts = CashAccount::withCount(['receiptVouchers', 'paymentVouchers'])->get();
        return view('cash_accounts.index', compact('accounts'));
    }

    public function create()
    {
        return view('cash_accounts.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $account = CashAccount::create($data);

        return redirect()->route('cash-accounts.index')->with('success', "تم إنشاء \"{$account->name}\" برصيد افتتاحي {$account->opening_balance}.");
    }

    public function show(CashAccount $cashAccount)
    {
        $receipts = $cashAccount->receiptVouchers()->latest()->limit(20)->get();
        $payments = $cashAccount->paymentVouchers()->latest()->limit(20)->get();

        return view('cash_accounts.show', ['account' => $cashAccount, 'receipts' => $receipts, 'payments' => $payments]);
    }

    public function edit(CashAccount $cashAccount)
    {
        return view('cash_accounts.edit', ['account' => $cashAccount]);
    }

    public function update(Request $request, CashAccount $cashAccount)
    {
        // لا نسمح بتعديل current_balance يدويًا؛ فقط بيانات الحساب
        $data = $this->validateData($request, updating: true);
        $cashAccount->update($data);

        return redirect()->route('cash-accounts.show', $cashAccount)->with('success', 'تم تحديث بيانات الحساب.');
    }

    private function validateData(Request $request, bool $updating = false): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|in:cash,bank',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:100',
            'iban' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ];

        if (!$updating) {
            $rules['opening_balance'] = 'required|numeric|min:0';
        }

        return $request->validate($rules);
    }
}
