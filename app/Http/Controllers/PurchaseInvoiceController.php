<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = PurchaseInvoice::query()
            ->with('supplier')
            ->when($request->supplier_id, fn($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->latest()
            ->paginate(20);

        return view('purchase_invoices.index', compact('invoices'));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->get();
        return view('purchase_invoices.create', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $data = $this->validateInvoiceData($request);
        $items = $this->validateItemsData($request);

        $invoice = DB::transaction(function () use ($data, $items) {
            $invoice = PurchaseInvoice::create([...$data, 'status' => 'unpaid']);

            foreach ($items as $item) {
                $invoice->items()->create($item);
            }

            return $invoice->fresh();
        });

        return redirect()->route('purchase-invoices.show', $invoice)->with('success', 'تم تسجيل فاتورة الشراء بنجاح.');
    }

    public function show(PurchaseInvoice $purchaseInvoice)
    {
        $purchaseInvoice->load('supplier', 'items');
        return view('purchase_invoices.show', ['invoice' => $purchaseInvoice]);
    }

    public function cancel(PurchaseInvoice $purchaseInvoice)
    {
        $purchaseInvoice->update(['status' => 'cancelled']);
        return back()->with('success', 'تم إلغاء فاتورة الشراء.');
    }

    private function validateInvoiceData(Request $request): array
    {
        return $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'required|string|unique:purchase_invoices,invoice_number',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'category' => 'required|in:uniforms,equipment,maintenance,utilities,other',
            'notes' => 'nullable|string',
        ]);
    }

    private function validateItemsData(Request $request): array
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        return $validated['items'];
    }
}
