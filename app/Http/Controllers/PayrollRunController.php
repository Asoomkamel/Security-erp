<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePayrollRunRequest;
use App\Models\CashAccount;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Services\PayrollService;
use Illuminate\Http\Request;

class PayrollRunController extends Controller
{
    public function index()
    {
        $runs = PayrollRun::withCount('items')->orderByDesc('year')->orderByDesc('month')->paginate(20);
        return view('payroll.index', compact('runs'));
    }

    public function create()
    {
        return view('payroll.create');
    }

    /** إنشاء تشغيل رواتب جديد وتوليد بنوده تلقائيًا لكل الموظفين النشطين */
    public function store(StorePayrollRunRequest $request, PayrollService $payrollService)
    {
        $data = $request->validated();

        try {
            $run = $payrollService->createRun($data['month'], $data['year']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['month' => $e->getMessage()]);
        }

        return redirect()->route('payroll-runs.show', $run)
            ->with('success', "تم إنشاء {$run->label()} بـ {$run->items->count()} موظف. راجع البنود قبل الاعتماد.");
    }

    public function show(PayrollRun $payrollRun)
    {
        $payrollRun->load('items.employee', 'items.paymentVoucher');
        $cashAccounts = CashAccount::where('is_active', true)->get();

        return view('payroll.show', ['run' => $payrollRun, 'cashAccounts' => $cashAccounts]);
    }

    /** تعديل بند راتب موظف قبل الاعتماد (بدلات/خصومات إضافية) */
    public function updateItem(Request $request, PayrollItem $item)
    {
        if ($item->payrollRun->status !== 'draft') {
            return back()->withErrors(['run' => 'لا يمكن تعديل بنود تشغيل تم اعتماده بالفعل.']);
        }

        $data = $request->validate([
            'allowances' => 'required|numeric|min:0',
            'other_deductions' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $item->update($data);

        return back()->with('success', 'تم تحديث بند الراتب.');
    }

    /** اعتماد التشغيل بالكامل (يقفل التعديل، يصبح جاهزًا للصرف) */
    public function approve(PayrollRun $payrollRun, PayrollService $payrollService)
    {
        if ($payrollRun->status !== 'draft') {
            return back()->withErrors(['run' => 'هذا التشغيل معتمد بالفعل.']);
        }

        $payrollService->approve($payrollRun, auth()->id());

        return back()->with('success', 'تم اعتماد تشغيل الرواتب. يمكن الآن صرف الرواتب.');
    }

    /** صرف راتب موظف واحد */
    public function payItem(Request $request, PayrollItem $item, PayrollService $payrollService)
    {
        $data = $request->validate(['cash_account_id' => 'required|exists:cash_accounts,id']);
        $cashAccount = CashAccount::findOrFail($data['cash_account_id']);

        try {
            $payrollService->payItem($item, $cashAccount, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return back()->with('success', "تم صرف راتب {$item->employee->full_name}.");
    }

    /** صرف كل الرواتب غير المدفوعة في التشغيل دفعة واحدة */
    public function payAll(Request $request, PayrollRun $payrollRun, PayrollService $payrollService)
    {
        $data = $request->validate(['cash_account_id' => 'required|exists:cash_accounts,id']);
        $cashAccount = CashAccount::findOrFail($data['cash_account_id']);

        try {
            $vouchers = $payrollService->payAll($payrollRun, $cashAccount, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return back()->with('success', 'تم صرف ' . count($vouchers) . ' راتب بنجاح.');
    }
}
