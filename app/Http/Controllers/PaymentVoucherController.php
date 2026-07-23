<?php

namespace App\Http\Controllers;

use App\Models\CashAccount;
use App\Models\PaymentVoucher;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentVoucherController extends Controller
{
    public function index(Request $request)
    {
        $vouchers = PaymentVoucher::query()
            ->with('supplier', 'cashAccount', 'employee')
            ->when($request->purpose, fn($q) => $q->where('purpose', $request->purpose))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest('voucher_date')
            ->paginate(20);

        return view('payment_vouchers.index', compact('vouchers'));
    }

    public function create()
    {
        $cashAccounts = CashAccount::where('is_active', true)->get();
        $suppliers = Supplier::where('is_active', true)->get();

        return view('payment_vouchers.create', compact('cashAccounts', 'suppliers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cash_account_id' => 'required|exists:cash_accounts,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'purchase_invoice_id' => 'nullable|exists:purchase_invoices,id',
            'employee_id' => 'nullable|exists:employees,id',
            'payment_order_id' => 'nullable|exists:payment_orders,id',
            'voucher_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,check,pos',
            'purpose' => 'required|in:supplier_payment,salary,expense,other',
            'cost_category' => 'nullable|required_if:purpose,expense|in:rent,fuel,utilities,maintenance,transport,government_fees,other',
            'paid_to' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        // إن كان السند صادرًا عن أمر دفع، يجب أن يكون معتمدًا مسبقًا (لا صرف بدون اعتماد)
        if (!empty($data['payment_order_id'])) {
            $order = \App\Models\PaymentOrder::findOrFail($data['payment_order_id']);
            if ($order->status !== 'approved') {
                return back()->withInput()->withErrors([
                    'payment_order_id' => 'لا يمكن الصرف إلا من أمر دفع معتمد.',
                ]);
            }
        }

        // منع صرف مبلغ أكبر من المتبقي على فاتورة الشراء (إن وُجدت)
        if (!empty($data['purchase_invoice_id'])) {
            $invoice = PurchaseInvoice::findOrFail($data['purchase_invoice_id']);
            if ($data['amount'] > $invoice->remainingAmount()) {
                return back()->withInput()->withErrors([
                    'amount' => 'المبلغ أكبر من المتبقي على فاتورة الشراء (' . $invoice->remainingAmount() . ' ر.س).',
                ]);
            }
        }

        // منع الصرف من صندوق رصيده لا يكفي
        $cashAccount = CashAccount::findOrFail($data['cash_account_id']);
        if ($data['amount'] > $cashAccount->current_balance) {
            return back()->withInput()->withErrors([
                'amount' => "رصيد \"{$cashAccount->name}\" الحالي ({$cashAccount->current_balance}) لا يكفي لهذا المبلغ.",
            ]);
        }

        $paymentOrderId = data_get($data, 'payment_order_id');
        unset($data['payment_order_id']); // ليس عمودًا بجدول payment_vouchers

        $voucher = DB::transaction(function () use ($data, $paymentOrderId) {
            $voucher = PaymentVoucher::create([
                ...$data,
                'voucher_number' => $this->generateNumber(),
                'status' => 'confirmed',
                'created_by' => auth()->id(),
            ]);

            $voucher->load('cashAccount', 'purchaseInvoice');
            $voucher->applyEffects();

            if ($paymentOrderId) {
                \App\Models\PaymentOrder::whereKey($paymentOrderId)
                    ->update(['status' => 'paid', 'payment_voucher_id' => $voucher->id]);
            }

            return $voucher;
        });

        return redirect()->route('payment-vouchers.show', $voucher)->with('success', 'تم تسجيل سند الصرف بنجاح.');
    }

    public function show(PaymentVoucher $paymentVoucher)
    {
        $paymentVoucher->load('cashAccount', 'supplier', 'purchaseInvoice', 'employee');
        return view('payment_vouchers.show', ['voucher' => $paymentVoucher]);
    }

    /** إلغاء السند: يعكس تلقائيًا أثره على رصيد الصندوق وفاتورة الشراء المرتبطة */
    public function cancel(PaymentVoucher $paymentVoucher)
    {
        DB::transaction(fn() => $paymentVoucher->load('cashAccount', 'purchaseInvoice')->cancel());

        return back()->with('success', 'تم إلغاء سند الصرف وعكس أثره على الصندوق وفاتورة الشراء.');
    }

    private function generateNumber(): string
    {
        $year = now()->format('Y');
        return \App\Models\NumberSequence::next("PAYMENT_VOUCHER_{$year}", "PV-{$year}");
    }
}
