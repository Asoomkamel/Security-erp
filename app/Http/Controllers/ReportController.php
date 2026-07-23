<?php

namespace App\Http\Controllers;

use App\Exports\ClientStatementExport;
use App\Exports\FinancialSummaryExport;
use App\Exports\SupplierStatementExport;
use App\Models\ClientCompany;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\SalesInvoice;
use App\Models\Supplier;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /** لوحة تحكم رئيسية بأهم المؤشرات */
    public function dashboard(ReportService $reports)
    {
        $summary = $reports->financialSummary(now()->startOfMonth(), now()->endOfMonth());

        $kpis = [
            'active_employees' => Employee::where('status', 'active')->count(),
            'active_contracts' => Contract::active()->count(),
            'expiring_contracts' => Contract::expiringSoon(30)->count(),
            'unpaid_invoices_total' => SalesInvoice::unpaid()->sum('total_amount'),
            'overdue_invoices_count' => SalesInvoice::overdue()->count(),
        ];

        return view('reports.dashboard', ['summary' => $summary, 'kpis' => $kpis]);
    }

    /** الملخص المالي لفترة (افتراضيًا الشهر الحالي) */
    public function financialSummary(Request $request, ReportService $reports)
    {
        [$from, $to] = $this->resolvePeriod($request);
        $summary = $reports->financialSummary($from, $to);

        return view('reports.financial_summary', compact('summary'));
    }

    public function financialSummaryPdf(Request $request, ReportService $reports)
    {
        [$from, $to] = $this->resolvePeriod($request);
        $summary = $reports->financialSummary($from, $to);

        return Pdf::loadView('reports.pdf.financial_summary', compact('summary'))
            ->download("financial-summary-{$from->format('Y-m-d')}-to-{$to->format('Y-m-d')}.pdf");
    }

    public function financialSummaryExcel(Request $request, ReportService $reports)
    {
        [$from, $to] = $this->resolvePeriod($request);
        $summary = $reports->financialSummary($from, $to);

        return Excel::download(
            new FinancialSummaryExport($summary),
            "financial-summary-{$from->format('Y-m-d')}-to-{$to->format('Y-m-d')}.xlsx"
        );
    }

    /** كشف حساب شركة عميلة */
    public function clientStatement(ClientCompany $clientCompany, ReportService $reports)
    {
        $data = $reports->clientStatement($clientCompany);
        return view('reports.client_statement', $data);
    }

    public function clientStatementPdf(ClientCompany $clientCompany, ReportService $reports)
    {
        $data = $reports->clientStatement($clientCompany);

        return Pdf::loadView('reports.pdf.client_statement', $data)
            ->download("client-statement-{$clientCompany->id}.pdf");
    }

    public function clientStatementExcel(ClientCompany $clientCompany, ReportService $reports)
    {
        $data = $reports->clientStatement($clientCompany);

        return Excel::download(new ClientStatementExport($data['rows']), "client-statement-{$clientCompany->id}.xlsx");
    }

    /** كشف حساب مورد */
    public function supplierStatement(Supplier $supplier, ReportService $reports)
    {
        $data = $reports->supplierStatement($supplier);
        return view('reports.supplier_statement', $data);
    }

    public function supplierStatementExcel(Supplier $supplier, ReportService $reports)
    {
        $data = $reports->supplierStatement($supplier);

        return Excel::download(new SupplierStatementExport($data['rows']), "supplier-statement-{$supplier->id}.xlsx");
    }

    /** تقرير أداء العقود */
    public function contractsPerformance(ReportService $reports)
    {
        $data = $reports->contractsPerformance();
        return view('reports.contracts_performance', $data);
    }

    /** ملخص تشغيل رواتب شهر معين */
    public function payrollSummary(Request $request, ReportService $reports)
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        $data = $reports->payrollSummary($month, $year);

        return view('reports.payroll_summary', ['data' => $data, 'month' => $month, 'year' => $year]);
    }

    /** استخراج الفترة الزمنية من الطلب، افتراضيًا الشهر الحالي بالكامل */
    private function resolvePeriod(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->from) : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->to) : now()->endOfMonth();

        return [$from, $to];
    }
}
