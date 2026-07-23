<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;

class SiteApiController extends Controller
{
    public function index()
    {
        $sites = Site::where('is_active', true)->with('clientCompany')->paginate(30);

        return response()->json($sites);
    }

    public function show(Site $site)
    {
        return response()->json(['data' => $site->load('clientCompany')]);
    }

    public function guards(Site $site)
    {
        $guards = $site->activeGuards()->get();

        return response()->json(['data' => $guards]);
    }
}
