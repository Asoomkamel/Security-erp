<?php

namespace App\Http\Controllers;

use App\Models\ClientCompany;
use Illuminate\Http\Request;

class ClientCompanyController extends Controller
{
    public function index(Request $request)
    {
        $companies = ClientCompany::query()
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->withCount('sites')
            ->latest()
            ->paginate(20);

        return view('client_companies.index', compact('companies'));
    }

    public function create()
    {
        return view('client_companies.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $company = ClientCompany::create($data);

        return redirect()->route('client-companies.show', $company)
            ->with('success', 'تم إضافة الشركة العميلة بنجاح.');
    }

    public function show(ClientCompany $clientCompany)
    {
        $clientCompany->load('sites.activeGuards.employee');
        return view('client_companies.show', ['company' => $clientCompany]);
    }

    public function edit(ClientCompany $clientCompany)
    {
        return view('client_companies.edit', ['company' => $clientCompany]);
    }

    public function update(Request $request, ClientCompany $clientCompany)
    {
        $data = $this->validateData($request, $clientCompany->id);
        $clientCompany->update($data);

        return redirect()->route('client-companies.show', $clientCompany)
            ->with('success', 'تم تحديث بيانات الشركة.');
    }

    public function destroy(ClientCompany $clientCompany)
    {
        $clientCompany->delete();
        return redirect()->route('client-companies.index')->with('success', 'تم حذف الشركة.');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'commercial_register' => 'nullable|string|max:100',
            'tax_number' => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
    }
}
