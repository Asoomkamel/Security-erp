<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;

class InvoiceApiController extends Controller
{
    public function index(Request $request)
    {
        $invoices = SalesInvoice::query()
            ->with('clientCompany')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->client_company_id, fn($q) => $q->where('client_company_id', $request->client_company_id))
            ->paginate(30);

        return response()->json($invoices);
    }

    public function show(SalesInvoice $salesInvoice)
    {
        return response()->json(['data' => $salesInvoice->load('clientCompany', 'items.site')]);
    }
}
