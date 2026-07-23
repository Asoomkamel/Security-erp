@extends('layouts.app')
@section('title', 'لوحة التحكم')

@section('content')
    <h4 class="mb-4">لوحة التحكم — نظرة عامة ({{ now()->translatedFormat('F Y') }})</h4>

    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card p-3 text-center">
                <div class="text-muted small">الموظفين النشطين</div>
                <div class="kpi-value">{{ $kpis['active_employees'] }}</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card p-3 text-center">
                <div class="text-muted small">العقود النشطة</div>
                <div class="kpi-value">{{ $kpis['active_contracts'] }}</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card p-3 text-center">
                <div class="text-muted small">عقود قريبة الانتهاء</div>
                <div class="kpi-value text-warning">{{ $kpis['expiring_contracts'] }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="text-muted small">فواتير غير محصّلة</div>
                <div class="kpi-value text-danger">{{ number_format($kpis['unpaid_invoices_total'], 2) }} ر.س</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="text-muted small">فواتير متأخرة السداد</div>
                <div class="kpi-value text-danger">{{ $kpis['overdue_invoices_count'] }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="card p-3">
                <h6 class="mb-3">الإيرادات مقابل المصاريف (آخر 6 أشهر)</h6>
                <canvas id="revenueChart" height="250"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3">
                <h6 class="mb-3">حالة فواتير البيع</h6>
                <canvas id="invoicePieChart" height="250"></canvas>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card p-3">
                <h6 class="mb-3">عدد الموظفين لكل فرع</h6>
                <canvas id="branchBarChart" height="250"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-3">
                <h6 class="mb-3">أعمار الفواتير غير المسددة (Aging)</h6>
                <canvas id="agingChart" height="250"></canvas>
            </div>
        </div>
    </div>

    <div class="card p-4 mb-4">
        <h6 class="mb-3">الملخص المالي للشهر الحالي ({{ $summary['from'] }} → {{ $summary['to'] }})</h6>
        <div class="row g-3">
            <div class="col-md-3"><div class="text-muted small">مبيعات مفوترة</div><div class="fw-bold">{{ number_format($summary['sales_invoiced'], 2) }}</div></div>
            <div class="col-md-3"><div class="text-muted small">محصَّل من العملاء</div><div class="fw-bold text-success">{{ number_format($summary['sales_collected'], 2) }}</div></div>
            <div class="col-md-3"><div class="text-muted small">رواتب مصروفة</div><div class="fw-bold">{{ number_format($summary['salaries_paid'], 2) }}</div></div>
            <div class="col-md-3"><div class="text-muted small">مصاريف تشغيلية</div><div class="fw-bold">{{ number_format($summary['other_expenses'], 2) }}</div></div>
            <div class="col-md-3"><div class="text-muted small">صافي التدفق النقدي</div>
                <div class="fw-bold {{ $summary['net_cash_flow'] >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($summary['net_cash_flow'], 2) }}
                </div>
            </div>
            <div class="col-md-3"><div class="text-muted small">أرصدة الصناديق حاليًا</div><div class="fw-bold">{{ number_format($summary['cash_accounts_balance'], 2) }}</div></div>
        </div>
        <a href="{{ route('reports.financial-summary') }}" class="btn btn-sm btn-primary mt-3">عرض التقرير المالي الكامل</a>
    </div>

    <div class="row g-3">
        <div class="col-md-4"><a href="{{ route('reports.contracts-performance') }}" class="card p-3 text-decoration-none">📄 تقرير أداء العقود</a></div>
        <div class="col-md-4"><a href="{{ route('reports.payroll-summary') }}" class="card p-3 text-decoration-none">💰 ملخص الرواتب الشهري</a></div>
        <div class="col-md-4"><a href="{{ route('client-companies.index') }}" class="card p-3 text-decoration-none">🏢 الشركات العميلة</a></div>
    </div>

    @push('scripts')
    <script>
        const sarFormatter = new Intl.NumberFormat('ar-SA', { style: 'currency', currency: 'SAR', maximumFractionDigits: 0 });

        // 1) الإيرادات مقابل المصاريف (خط بياني لآخر 6 أشهر)
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: @json($chartData['months']),
                datasets: [
                    {
                        label: 'الإيرادات المحصَّلة',
                        data: @json($chartData['revenues']),
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25,135,84,.15)',
                        tension: 0.3,
                        fill: true,
                    },
                    {
                        label: 'المصاريف (رواتب + موردون + تكاليف)',
                        data: @json($chartData['expenses']),
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220,53,69,.15)',
                        tension: 0.3,
                        fill: true,
                    },
                ],
            },
            options: {
                plugins: { tooltip: { callbacks: { label: (ctx) => sarFormatter.format(ctx.raw) } } },
                scales: { y: { ticks: { callback: (v) => sarFormatter.format(v) } } },
            },
        });

        // 2) حالة فواتير البيع (دائري)
        new Chart(document.getElementById('invoicePieChart'), {
            type: 'doughnut',
            data: {
                labels: ['مسددة', 'غير مسددة', 'مسددة جزئيًا', 'ملغاة'],
                datasets: [{
                    data: [
                        {{ $invoiceStats['paid'] }},
                        {{ $invoiceStats['unpaid'] }},
                        {{ $invoiceStats['partially_paid'] }},
                        {{ $invoiceStats['cancelled'] }},
                    ],
                    backgroundColor: ['#198754', '#dc3545', '#ffc107', '#6c757d'],
                }],
            },
        });

        // 3) عدد الموظفين لكل فرع (أعمدة)
        new Chart(document.getElementById('branchBarChart'), {
            type: 'bar',
            data: {
                labels: @json($branchData['names']),
                datasets: [{
                    label: 'عدد الموظفين',
                    data: @json($branchData['counts']),
                    backgroundColor: '#0d6efd',
                }],
            },
            options: { plugins: { legend: { display: false } } },
        });

        // 4) أعمار الفواتير غير المسددة (أعمدة)
        new Chart(document.getElementById('agingChart'), {
            type: 'bar',
            data: {
                labels: ['0-30 يوم', '31-60 يوم', '61-90 يوم', 'أكثر من 90 يوم'],
                datasets: [{
                    label: 'المبلغ غير المسدد',
                    data: [
                        {{ $agingData['0-30'] }},
                        {{ $agingData['31-60'] }},
                        {{ $agingData['61-90'] }},
                        {{ $agingData['90+'] }},
                    ],
                    backgroundColor: ['#ffc107', '#fd7e14', '#dc3545', '#842029'],
                }],
            },
            options: {
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => sarFormatter.format(ctx.raw) } },
                },
                scales: { y: { ticks: { callback: (v) => sarFormatter.format(v) } } },
            },
        });
    </script>
    @endpush
@endsection
