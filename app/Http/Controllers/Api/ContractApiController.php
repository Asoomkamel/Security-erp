<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\Request;

class ContractApiController extends Controller
{
    public function index(Request $request)
    {
        $contracts = Contract::query()
            ->with('clientCompany')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->paginate(30);

        return response()->json($contracts);
    }

    public function show(Contract $contract)
    {
        return response()->json(['data' => $contract->load('clientCompany', 'contractSites.site')]);
    }
}
