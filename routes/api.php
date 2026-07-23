<?php

use App\Http\Controllers\Api\AttendanceApiController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\ContractApiController;
use App\Http\Controllers\Api\EmployeeApiController;
use App\Http\Controllers\Api\InvoiceApiController;
use App\Http\Controllers\Api\SiteApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| مسارات API (Laravel Sanctum - Bearer Token)
|--------------------------------------------------------------------------
| الاستخدام: تسجيل الدخول عبر /v1/auth/login يعيد access_token، يُرسَل بعدها
| بكل طلب كـ Header: Authorization: Bearer {token}
*/

Route::post('/v1/auth/login', [AuthApiController::class, 'login']);

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {

    Route::post('auth/logout', [AuthApiController::class, 'logout']);
    Route::get('auth/me', [AuthApiController::class, 'me']);

    // الحضور والانصراف (لتطبيق جوال الحارس/المشرف مستقبلًا)
    Route::get('attendance/today', [AttendanceApiController::class, 'today']);
    Route::post('attendance/check-in', [AttendanceApiController::class, 'checkIn']);
    Route::post('attendance/check-out/{attendance}', [AttendanceApiController::class, 'checkOut']);
    Route::get('attendance/history', [AttendanceApiController::class, 'history']);

    // الموظفون - للأدوار الإدارية فقط
    Route::middleware('role:admin,hr,manager')->group(function () {
        Route::get('employees', [EmployeeApiController::class, 'index']);
        Route::get('employees/{employee}', [EmployeeApiController::class, 'show']);
        Route::get('employees/{employee}/attendance', [EmployeeApiController::class, 'attendance']);
    });

    // المواقع
    Route::get('sites', [SiteApiController::class, 'index']);
    Route::get('sites/{site}', [SiteApiController::class, 'show']);
    Route::get('sites/{site}/guards', [SiteApiController::class, 'guards']);

    // العقود
    Route::middleware('role:admin,accountant,manager')->group(function () {
        Route::get('contracts', [ContractApiController::class, 'index']);
        Route::get('contracts/{contract}', [ContractApiController::class, 'show']);
    });

    // الفواتير
    Route::middleware('role:admin,accountant')->group(function () {
        Route::get('invoices', [InvoiceApiController::class, 'index']);
        Route::get('invoices/{salesInvoice}', [InvoiceApiController::class, 'show']);
    });
});
