<?php

namespace App\Http\Controllers;

use App\Models\PaymentOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;

class PaymentOrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = PaymentOrder::query()
            ->with('supplier', 'purchaseInvoice')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return view('payment_orders.index', compact('orders'));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->get();
        return view('payment_orders.create', compact('suppliers'));
    }

    /** طلب دفع جديد (ينشئه مثلًا مسؤول المشتريات، بانتظار اعتماد المحاسب/الأدمن) */
    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'purchase_invoice_id' => 'nullable|exists:purchase_invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'due_date' => 'required|date',
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $order = PaymentOrder::create([
            ...$data,
            'order_number' => \App\Models\NumberSequence::next('PAYMENT_ORDER_' . now()->format('Y'), 'PO-' . now()->format('Y')),
            'status' => 'pending',
        ]);

        return redirect()->route('payment-orders.show', $order)->with('success', 'تم إنشاء طلب الدفع، بانتظار الاعتماد.');
    }

    public function show(PaymentOrder $paymentOrder)
    {
        $paymentOrder->load('supplier', 'purchaseInvoice', 'approver', 'paymentVoucher');
        return view('payment_orders.show', ['order' => $paymentOrder]);
    }

    /** اعتماد الطلب — لا يصرف المبلغ تلقائيًا، فقط يسمح بإنشاء سند الصرف لاحقًا */
    public function approve(PaymentOrder $paymentOrder)
    {
        if ($paymentOrder->status !== 'pending') {
            return back()->withErrors(['status' => 'هذا الطلب ليس بانتظار الاعتماد.']);
        }

        $paymentOrder->approve(auth()->id());

        return back()->with('success', 'تم اعتماد طلب الدفع. يمكن الآن إنشاء سند الصرف له.');
    }

    public function reject(PaymentOrder $paymentOrder)
    {
        $paymentOrder->reject(auth()->id());

        return back()->with('success', 'تم رفض طلب الدفع.');
    }
}
