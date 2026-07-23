<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractRequest;
use App\Models\ClientCompany;
use App\Models\Contract;
use App\Models\ContractSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $contracts = Contract::query()
            ->with('clientCompany')
            ->when($request->client_company_id, fn($q) => $q->where('client_company_id', $request->client_company_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('contract_number', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(20);

        return view('contracts.index', compact('contracts'));
    }

    public function create()
    {
        $clientCompanies = ClientCompany::where('is_active', true)->get();
        return view('contracts.create', compact('clientCompanies'));
    }

    /**
     * إنشاء عقد جديد مع قائمة المواقع وأسعارها دفعة واحدة
     * expected request: sites = [ ['site_id' => 1, 'guards_count' => 4, 'unit_price' => 1200], ... ]
     */
    public function store(StoreContractRequest $request)
    {
        $validated = $request->validated();
        $sitesData = $validated['sites'];
        $data = collect($validated)->except('sites')->toArray();

        $contract = DB::transaction(function () use ($data, $sitesData) {
            $contract = Contract::create($data);

            foreach ($sitesData as $siteRow) {
                $contract->contractSites()->create($siteRow);
            }

            // تحديث القيمة الإجمالية المحسوبة للعقد من مجموع المواقع (إن لم تُدخل يدويًا)
            if (empty($contract->total_value)) {
                $contract->update(['total_value' => $contract->calculatedTotal()]);
            }

            // إن كان العقد شهري متجدد، نحسب أول موعد استحقاق فاتورة
            if ($contract->billing_cycle !== 'one_time' && !$contract->next_invoice_due_at) {
                $contract->update([
                    'next_invoice_due_at' => $contract->calculateNextInvoiceDate($contract->start_date),
                ]);
            }

            return $contract;
        });

        return redirect()->route('contracts.show', $contract)->with('success', __('messages.created_success'));
    }

    public function show(Contract $contract)
    {
        $contract->load('clientCompany', 'contractSites.site');
        return view('contracts.show', compact('contract'));
    }

    public function edit(Contract $contract)
    {
        $contract->load('contractSites.site');
        $clientCompanies = ClientCompany::where('is_active', true)->get();
        return view('contracts.edit', compact('contract', 'clientCompanies'));
    }

    public function update(StoreContractRequest $request, Contract $contract)
    {
        $validated = $request->validated();
        $sitesData = $validated['sites'];
        $data = collect($validated)->except('sites')->toArray();

        DB::transaction(function () use ($contract, $data, $sitesData) {
            $contract->update($data);

            // إعادة بناء قائمة مواقع العقد بالكامل (أبسط وأضمن من تحديث جزئي)
            $contract->contractSites()->delete();
            foreach ($sitesData as $siteRow) {
                $contract->contractSites()->create($siteRow);
            }

            if (empty($data['total_value'])) {
                $contract->update(['total_value' => $contract->calculatedTotal()]);
            }
        });

        return redirect()->route('contracts.show', $contract)->with('success', __('messages.updated_success'));
    }

    public function destroy(Contract $contract)
    {
        $contract->delete();
        return redirect()->route('contracts.index')->with('success', __('messages.deleted_success'));
    }

    /** إلغاء العقد (بدون حذفه) */
    public function cancel(Contract $contract)
    {
        $contract->update(['status' => 'cancelled']);
        return back()->with('success', __('messages.cancelled_success'));
    }

    /** تفعيل عقد كان في حالة مسودة */
    public function activate(Contract $contract)
    {
        $contract->update(['status' => 'active']);
        return back()->with('success', __('messages.approved_success'));
    }

}
