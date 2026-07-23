<?php

namespace App\Http\Controllers;

use App\Models\CashAccount;
use App\Models\ClientCompany;
use App\Models\ReceiptVoucher;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceiptVoucherController extends Controller
{
    public function index(Request $request)
    {
        $vouchers = ReceiptVoucher::query()
            ->with('clientCompany', 'cashAccount')
            ->when($request->client_company_id, fn($q) => $q->where('client_company_id', $request->client_company_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest('voucher_date')
            ->paginate(20);

        return view('receipt_vouchers.index', compact('vouchers'));
    }

    public function create(Request $request)
    {
        $cashAccounts = CashAccount::where('is_active', true)->get();
        $clientCompanies = ClientCompany::where('is_active', true)->get();

        // لو جاء من صفحة فاتورة بيع معينة لتحصيلها مباشرة
        $invoice = $request->sales_invoice_id ? SalesInvoice::find($request->sales_invoice_id) : null;

        return view('receipt_vouchers.create', compact('cashAccounts', 'clientCompanies', 'invoice'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cash_account_id' => 'required|exists:cash_accounts,id',
            'client_company_id' => 'nullable|exists:client_companies,id',
            'sales_invoice_id' => 'nullable|exists:sales_invoices,id',
            'voucher_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,check,pos',
            'received_from' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        // منع تحصيل مبلغ أكبر من المتبقي على الفاتورة (إن كان السند مرتبطًا بفاتورة)
        if (!empty($data['sales_invoice_id'])) {
            $invoice = SalesInvoice::findOrFail($data['sales_invoice_id']);
            if ($data['amount'] > $invoice->remainingAmount()) {
                return back()->withInput()->withErrors([
                    'amount' => 'المبلغ أكبر من المتبقي على الفاتورة (' . $invoice->remainingAmount() . ' ر.س).',
                ]);
            }
        }

        $voucher = DB::transaction(function () use ($data) {
            $voucher = ReceiptVoucher::create([
                ...$data,
                'voucher_number' => $this->generateNumber(),
                'status' => 'confirmed',
                'created_by' => auth()->id(),
            ]);

            $voucher->load('cashAccount', 'salesInvoice');
            $voucher->applyEffects();

            return $voucher;
        });

        return redirect()->route('receipt-vouchers.show', $voucher)->with('success', 'تم تسجيل سند القبض بنجاح.');
    }

    public function show(ReceiptVoucher $receiptVoucher)
    {
        $receiptVoucher->load('cashAccount', 'clientCompany', 'salesInvoice');
        return view('receipt_vouchers.show', ['voucher' => $receiptVoucher]);
    }

    /** إلغاء السند: يعكس تلقائيًا أثره على رصيد الصندوق والفاتورة المرتبطة */
    public function cancel(ReceiptVoucher $receiptVoucher)
    {
        DB::transaction(fn() => $receiptVoucher->load('cashAccount', 'salesInvoice')->cancel());

        return back()->with('success', 'تم إلغاء سند القبض وعكس أثره على الصندوق والفاتورة.');
    }

    private function generateNumber(): string
    {
        $year = now()->format('Y');
        return \App\Models\NumberSequence::next("RECEIPT_VOUCHER_{$year}", "RV-{$year}");
    }
}
