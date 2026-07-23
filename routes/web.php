<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CashAccountController;
use App\Http\Controllers\ClientCompanyController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\EmployeeAdvanceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PaymentOrderController;
use App\Http\Controllers\PaymentVoucherController;
use App\Http\Controllers\PayrollRunController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\ReceiptVoucherController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Phase 1 → Phase 7 Routes
|--------------------------------------------------------------------------
| Phase 1-6: انظر التعليقات أعلاه بالإصدارات السابقة
| Phase 7: مستفاد من مراجعة نسخة ثانية — الفروع، الحضور الفعلي، سجل التدقيق،
|          بيانات موظف أدق، مولّد أرقام آمن، جاهزية ZATCA، حد ائتماني، أوامر دفع
|
| ملاحظة: مسارات تسجيل الدخول (login/register) تُضاف تلقائيًا عبر Laravel
| Breeze بعد تنفيذ أمر: php artisan breeze:install
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/', fn() => redirect()->route('reports.dashboard'))->name('home');

    // الموظفين والحراس - كل الأدوار المصرح لها تصل، الحذف للأدمن/الموارد البشرية فقط
    Route::middleware('role:admin,hr,manager')->group(function () {
        Route::resource('employees', EmployeeController::class);
    });

    // الشركات العميلة - أدمن ومحاسب ومدير
    Route::middleware('role:admin,accountant,manager')->group(function () {
        Route::resource('client-companies', ClientCompanyController::class);
        Route::resource('sites', SiteController::class);
    });

    // تعيين الحراس على المواقع - أدمن ومدير ومشرف
    Route::middleware('role:admin,manager,supervisor')->group(function () {
        Route::post('assignments', [AssignmentController::class, 'store'])->name('assignments.store');
        Route::post('assignments/{assignment}/end', [AssignmentController::class, 'end'])->name('assignments.end');
    });

    // العقود - أدمن ومحاسب ومدير (الموارد البشرية والمشرف لا يحتاجون رؤية القيم المالية للعقود)
    Route::middleware('role:admin,accountant,manager')->group(function () {
        Route::resource('contracts', ContractController::class);
        Route::post('contracts/{contract}/cancel', [ContractController::class, 'cancel'])->name('contracts.cancel');
        Route::post('contracts/{contract}/activate', [ContractController::class, 'activate'])->name('contracts.activate');
    });

    // الفواتير والموردين - أدمن ومحاسب فقط (بيانات مالية حساسة)
    Route::middleware('role:admin,accountant')->group(function () {
        Route::resource('suppliers', SupplierController::class);

        Route::resource('sales-invoices', SalesInvoiceController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('sales-invoices/{salesInvoice}/cancel', [SalesInvoiceController::class, 'cancel'])->name('sales-invoices.cancel');

        Route::resource('purchase-invoices', PurchaseInvoiceController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('purchase-invoices/{purchaseInvoice}/cancel', [PurchaseInvoiceController::class, 'cancel'])->name('purchase-invoices.cancel');
    });

    // الصناديق والبنوك والسندات - أدمن ومحاسب فقط
    Route::middleware('role:admin,accountant')->group(function () {
        Route::resource('cash-accounts', CashAccountController::class)->except(['destroy']);

        Route::resource('receipt-vouchers', ReceiptVoucherController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('receipt-vouchers/{receiptVoucher}/cancel', [ReceiptVoucherController::class, 'cancel'])->name('receipt-vouchers.cancel');

        Route::resource('payment-vouchers', PaymentVoucherController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('payment-vouchers/{paymentVoucher}/cancel', [PaymentVoucherController::class, 'cancel'])->name('payment-vouchers.cancel');
    });

    // الرواتب والسلف - أدمن ومحاسب ومسؤول موارد بشرية
    Route::middleware('role:admin,accountant,hr')->group(function () {
        Route::resource('advances', EmployeeAdvanceController::class)->only(['index', 'create', 'store', 'show']);

        Route::resource('payroll-runs', PayrollRunController::class)->only(['index', 'create', 'store', 'show']);
        Route::patch('payroll-items/{item}', [PayrollRunController::class, 'updateItem'])->name('payroll-items.update');
        Route::post('payroll-runs/{payrollRun}/approve', [PayrollRunController::class, 'approve'])->name('payroll-runs.approve');
        Route::post('payroll-items/{item}/pay', [PayrollRunController::class, 'payItem'])->name('payroll-items.pay');
        Route::post('payroll-runs/{payrollRun}/pay-all', [PayrollRunController::class, 'payAll'])->name('payroll-runs.pay-all');
    });

    // التقارير - أدمن ومحاسب ومدير
    Route::middleware('role:admin,accountant,manager')->group(function () {
        Route::get('dashboard', [ReportController::class, 'dashboard'])->name('reports.dashboard');

        Route::get('reports/financial-summary', [ReportController::class, 'financialSummary'])->name('reports.financial-summary');
        Route::get('reports/financial-summary/pdf', [ReportController::class, 'financialSummaryPdf'])->name('reports.financial-summary.pdf');
        Route::get('reports/financial-summary/excel', [ReportController::class, 'financialSummaryExcel'])->name('reports.financial-summary.excel');

        Route::get('reports/clients/{clientCompany}', [ReportController::class, 'clientStatement'])->name('reports.client-statement');
        Route::get('reports/clients/{clientCompany}/pdf', [ReportController::class, 'clientStatementPdf'])->name('reports.client-statement.pdf');
        Route::get('reports/clients/{clientCompany}/excel', [ReportController::class, 'clientStatementExcel'])->name('reports.client-statement.excel');

        Route::get('reports/suppliers/{supplier}', [ReportController::class, 'supplierStatement'])->name('reports.supplier-statement');
        Route::get('reports/suppliers/{supplier}/excel', [ReportController::class, 'supplierStatementExcel'])->name('reports.supplier-statement.excel');

        Route::get('reports/contracts-performance', [ReportController::class, 'contractsPerformance'])->name('reports.contracts-performance');
        Route::get('reports/payroll-summary', [ReportController::class, 'payrollSummary'])->name('reports.payroll-summary');
    });

    // الحضور والانصراف - أدمن ومدير ومشرف ومسؤول موارد بشرية (المشرف يسجّل، البقية يراجعون)
    Route::middleware('role:admin,manager,supervisor,hr')->group(function () {
        Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('attendance/create', [AttendanceController::class, 'create'])->name('attendance.create');
        Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::get('attendance/employees/{employee}/summary', [AttendanceController::class, 'monthlySummary'])->name('attendance.summary');
    });

    // أوامر الدفع - أدمن ومحاسب فقط (مرحلة اعتماد قبل الصرف الفعلي)
    Route::middleware('role:admin,accountant')->group(function () {
        Route::resource('payment-orders', PaymentOrderController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('payment-orders/{paymentOrder}/approve', [PaymentOrderController::class, 'approve'])->name('payment-orders.approve');
        Route::post('payment-orders/{paymentOrder}/reject', [PaymentOrderController::class, 'reject'])->name('payment-orders.reject');
    });
});
