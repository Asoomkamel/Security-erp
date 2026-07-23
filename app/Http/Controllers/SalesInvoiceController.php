<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalesInvoiceRequest;
use App\Models\ClientCompany;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = SalesInvoice::query()
            ->with('clientCompany')
            ->when($request->client_company_id, fn($q) => $q->where('client_company_id', $request->client_company_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('invoice_number', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(20);

        return view('sales_invoices.index', compact('invoices'));
    }

    public function create()
    {
        $clientCompanies = ClientCompany::where('is_active', true)->get();
        return view('sales_invoices.create', compact('clientCompanies'));
    }

    /**
     * إنشاء فاتورة بيع يدوية (لخدمات إضافية خارج عقد شهري، مثل مهمة حراسة مؤقتة لمناسبة)
     * expected: items = [ ['description' => '...', 'quantity' => 1, 'unit_price' => 500], ... ]
     */
    public function store(StoreSalesInvoiceRequest $request)
    {
        $validated = $request->validated();
        $items = $validated['items'];
        $data = collect($validated)->except('items')->toArray();

        // تحقق فعلي من الحد الائتماني (مستفاد من مراجعة نسخة ثانية، مفعَّل هنا وليس مجرد حقل شكلي)
        $estimatedTotal = collect($items)->sum(fn($i) => $i['quantity'] * $i['unit_price']) * 1.15;
        $client = ClientCompany::findOrFail($data['client_company_id']);

        if (!$client->hasAvailableCredit($estimatedTotal)) {
            return back()->withInput()->withErrors([
                'client_company_id' => "هذه الفاتورة تتجاوز الحد الائتماني المسموح لعميل \"{$client->name}\" ({$client->credit_limit} ر.س).",
            ]);
        }

        $invoice = DB::transaction(function () use ($data, $items) {
            $invoice = SalesInvoice::create([
                ...$data,
                'invoice_number' => SalesInvoice::generateNumber(),
                'source' => 'manual',
                'status' => 'unpaid',
            ]);

            foreach ($items as $item) {
                $invoice->items()->create($item);
            }

            return $invoice->fresh();
        });

        return redirect()->route('sales-invoices.show', $invoice)->with('success', 'تم إنشاء فاتورة البيع بنجاح.');
    }

    public function show(SalesInvoice $salesInvoice)
    {
        $salesInvoice->load('clientCompany', 'contract', 'items.site');
        return view('sales_invoices.show', ['invoice' => $salesInvoice]);
    }

    /** إلغاء فاتورة (بدلاً من حذفها، للحفاظ على تسلسل الأرقام) */
    public function cancel(SalesInvoice $salesInvoice)
    {
        $salesInvoice->update(['status' => 'cancelled']);
        return back()->with('success', 'تم إلغاء الفاتورة.');
    }
}
