<?php

namespace App\Http\Controllers;

use App\Models\ClientCompany;
use App\Models\Site;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index(Request $request)
    {
        $sites = Site::query()
            ->with('clientCompany')
            ->when($request->client_company_id, fn($q) => $q->where('client_company_id', $request->client_company_id))
            ->latest()
            ->paginate(20);

        return view('sites.index', compact('sites'));
    }

    public function create()
    {
        $clientCompanies = ClientCompany::where('is_active', true)->get();
        return view('sites.create', compact('clientCompanies'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $site = Site::create($data);

        return redirect()->route('sites.show', $site)->with('success', 'تم إضافة الموقع بنجاح.');
    }

    public function show(Site $site)
    {
        $site->load('clientCompany', 'activeGuards.employee');
        return view('sites.show', compact('site'));
    }

    public function edit(Site $site)
    {
        $clientCompanies = ClientCompany::where('is_active', true)->get();
        return view('sites.edit', compact('site', 'clientCompanies'));
    }

    public function update(Request $request, Site $site)
    {
        $data = $this->validateData($request);
        $site->update($data);

        return redirect()->route('sites.show', $site)->with('success', 'تم تحديث بيانات الموقع.');
    }

    public function destroy(Site $site)
    {
        $site->delete();
        return redirect()->route('sites.index')->with('success', 'تم حذف الموقع.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'client_company_id' => 'required|exists:client_companies,id',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'site_manager_name' => 'nullable|string|max:255',
            'site_manager_phone' => 'nullable|string|max:20',
            'required_guards_count' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);
    }
}
