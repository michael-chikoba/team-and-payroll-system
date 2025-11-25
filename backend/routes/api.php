<?php
// routes/api.php (updated with new route)
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\PayslipController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\ManagerController;
use App\Http\Controllers\Api\AdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskCommentController;
/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

      
/*
|--------------------------------------------------------------------------
| Protected Routes (Authentication Required)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {
   
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
   
    // Dashboard (Available to all authenticated users)
    Route::get('/dashboard', [DashboardController::class, 'index']);
   
    // Profile (Available to all authenticated users)
    // Route::get('/profile', [EmployeeController::class, 'profile']);
    // Route::put('/profile', [EmployeeController::class, 'updateProfile']);
   
    /*
    |--------------------------------------------------------------------------
    | Employee Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:employee'])->prefix('employee')->group(function () {
      
        // Attendance Routes
        Route::prefix('attendance')->group(function () {
            Route::get('today-status', [AttendanceController::class, 'todayStatus']);
            Route::get('stats', [AttendanceController::class, 'summary']);
            Route::get('history', [AttendanceController::class, 'history']);
            Route::get('/', [AttendanceController::class, 'history']);
            Route::post('clock-in', [AttendanceController::class, 'clockIn']);
            Route::post('clock-out', [AttendanceController::class, 'clockOut']);
            Route::post('force-reset', [AttendanceController::class, 'forceReset']);
        });
      
        // Leave Routes
        Route::prefix('leaves')->group(function () {
            Route::get('balance', [LeaveController::class, 'balance']);
            Route::get('/', [LeaveController::class, 'index']);
            Route::post('/', [LeaveController::class, 'store']);
        });
        Route::post('/leaves/{leave}/cancel', [LeaveController::class, 'cancel']);
      
        // Payslip Routes for Employees
        Route::prefix('payslips')->group(function () {
            Route::get('/', [PayslipController::class, 'index']); // Employee's own payslips
            Route::get('/{payslip}', [PayslipController::class, 'show']);
            Route::get('/{payslip}/download', [PayslipController::class, 'download']);
        });
      
        // Leave Balance Initialization (Temporary/Setup endpoint)
        Route::post('initialize-leave-balances', [EmployeeController::class, 'initializeLeaveBalances']);
    });
   
    /*
    |--------------------------------------------------------------------------
    | Manager Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:manager'])->prefix('manager')->group(function () {
                     // Attendance Routes
        Route::prefix('attendance')->group(function () {
            Route::get('today-status', [AttendanceController::class, 'todayStatus']);
            Route::get('stats', [AttendanceController::class, 'summary']);
            Route::get('history', [AttendanceController::class, 'history']);
            Route::get('/', [AttendanceController::class, 'history']);
            Route::post('clock-in', [AttendanceController::class, 'clockIn']);
            Route::post('clock-out', [AttendanceController::class, 'clockOut']);
            Route::post('force-reset', [AttendanceController::class, 'forceReset']);
        });
        // Employee Management
        Route::get('employees', [ManagerController::class, 'employees']);
        Route::get('employees/{employee}', [ManagerController::class, 'employeeDetails']);
      
        // Attendance Monitoring
        Route::get('attendance', [ManagerController::class, 'attendanceReport']);
        Route::post('attendance/{employee}/mark-present', [ManagerController::class, 'markPresent']);
            // ✅ FIXED: Attendance History Routes
    Route::prefix('attendance')->group(function () {
        Route::get('history', [AttendanceController::class, 'history']); // Manager's team history
        Route::get('history/{date}', [AttendanceController::class, 'historyByDate']); // Specific date
        Route::get('{employee}/history', [AttendanceController::class, 'employeeHistory']); // Specific employee
    });
   
        // Leave Management
        Route::prefix('leaves')->group(function () {
            Route::get('pending', [LeaveController::class, 'pendingLeaves']);
            Route::post('{leave}/approve', [LeaveController::class, 'approve']);
            Route::post('{leave}/reject', [LeaveController::class, 'reject']);
        });
      
        // Reports
        Route::prefix('reports')->group(function () {
            Route::get('team', [ReportController::class, 'teamReport']);
            Route::get('productivity', [ReportController::class, 'productivityReport']);
        });
    });
    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        Route::get('/leaves/current-month', [LeaveController::class, 'currentMonthLeaves']);
          // Attendance Routes
        Route::prefix('attendance')->group(function () {
            Route::get('today-status', [AttendanceController::class, 'todayStatus']);
            Route::get('stats', [AttendanceController::class, 'summary']);
            Route::get('history', [AttendanceController::class, 'history']);
            Route::get('/', [AttendanceController::class, 'history']);
            Route::post('clock-in', [AttendanceController::class, 'clockIn']);
            Route::post('clock-out', [AttendanceController::class, 'clockOut']);
            Route::post('force-reset', [AttendanceController::class, 'forceReset']);
        });
        // Employee Management
        Route::apiResource('employees', EmployeeController::class);
        Route::get('managers', [EmployeeController::class, 'managers']);
        Route::get('/attendance/history', [AttendanceController::class, 'adminHistory']);
        // Attendance Management
        Route::prefix('attendance')->group(function () {
            Route::get('status', [AttendanceController::class, 'getAttendanceStatus']);
            Route::post('{employee}/mark-present', [AttendanceController::class, 'markPresent']);
            Route::get('current-statuses', [AttendanceController::class, 'currentStatuses']);
            Route::post('bulk-mark-present', [AttendanceController::class, 'bulkMarkPresent']);
        });
      
        // Report Generation
        Route::post('generate/attendance', [ReportController::class, 'generateAttendanceReport']);
        Route::post('generate/leave', [ReportController::class, 'generateLeaveReport']);
        Route::post('generate/payroll', [ReportController::class, 'generatePayrollReport']);
        Route::post('generate/organization', [ReportController::class, 'generateOrganizationReport']);
        Route::post('download/{type}', [ReportController::class, 'downloadReport']);
      
        // Payroll Management
        Route::prefix('payroll')->group(function () {
            Route::post('process', [PayrollController::class, 'processPayroll']);
            Route::get('employees-summary', [PayrollController::class, 'employeesSummary']); // New endpoint for pre-calculated summary
            Route::post('preview', [PayrollController::class, 'preview']);
            Route::get('history', [PayrollController::class, 'history']);
            Route::get('cycles', [PayrollController::class, 'cycles']);
            Route::post('/update-status', [PayrollController::class, 'updateStatus']);
            Route::get('/{payroll}', [PayrollController::class, 'show']);
            Route::delete('/{payroll}', [PayrollController::class, 'destroy']);
        });
      
        // Payslip Management - ADMIN ROUTES
        Route::prefix('payslips')->group(function () {
            Route::get('/', [PayslipController::class, 'adminIndex']); // List all payslips with filters
            Route::post('/', [PayslipController::class, 'store']); // CREATE new payslip
            Route::get('/{payslip}', [PayslipController::class, 'show']); // Get single payslip
            Route::post('/generate', [PayslipController::class, 'generate']); // Generate from payroll
            Route::post('/bulk-download', [PayslipController::class, 'bulkDownload']);
            Route::post('/{payslip}/generate-pdf', [PayslipController::class, 'generatePdf']);
            Route::get('/{payslip}/download', [PayslipController::class, 'download']);
            Route::post('/{payslip}/send', [PayslipController::class, 'sendNotifications']);
        });
      
        // Tax Configuration
        Route::get('tax-configuration', [AdminController::class, 'getTaxConfiguration']);
        Route::post('/update-tax-configuration', [AdminController::class, 'updateTaxConfiguration']);
        Route::get('payroll/employee/{employeeId}/payslip', [PayrollController::class, 'viewEmployeePayslip']);
        //Route::get('/tax-configuration/active', [AdminController::class, 'getTaxConfiguration']);

        // Reports
      
Route::prefix('reports')->group(function () {
    // Stats and basic data
    Route::get('stats', [ReportController::class, 'getAdminStats']);
    Route::get('report-params/{type}', [ReportController::class, 'getReportParams']);
    Route::get('generated-reports', [ReportController::class, 'getGeneratedReports']);
    
    // Report generation endpoints
    Route::post('generate/attendance', [ReportController::class, 'generateAttendanceReport']);
    Route::post('generate/leave', [ReportController::class, 'generateLeaveReport']);
    Route::post('generate/payroll', [ReportController::class, 'generatePayrollReport']);
    Route::post('generate/organization', [ReportController::class, 'generateOrganizationReport']);
    Route::post('download/{type}', [ReportController::class, 'downloadReport']);
    
    // Predefined report views
    Route::get('payroll', [ReportController::class, 'payrollReport']);
    Route::get('attendance', [ReportController::class, 'attendanceReport']);
    Route::get('leave', [ReportController::class, 'leaveReport']);
});
      
        // System Management
        Route::get('audit-logs', [AdminController::class, 'auditLogs']);
        Route::get('settings', [AdminController::class, 'getSettings']);
        Route::put('settings', [AdminController::class, 'updateSettings']);
    });
});
Route::middleware('auth:sanctum')->group(function () {
    // Employee/Manager list routes
    Route::get('/employees', [TaskController::class, 'getEmployees'])->middleware('role:manager');
    Route::get('/tasks/employees/simple', [TaskController::class, 'getSimpleEmployees']);    
    
    // Task routes
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store'])->middleware('role:manager');
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->middleware('role:manager');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->middleware('role:manager');
   
    // Comment routes
    Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store']);
    Route::delete('/comments/{comment}', [TaskCommentController::class, 'destroy']);
});
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/profile', [EmployeeController::class, 'profile']);
    Route::put('/profile', [EmployeeController::class, 'updateProfile']);
Route::post('/profile/password', [EmployeeController::class, 'updatePassword']);    // Employee Documents Routes (Available to all authenticated users with employee record)
    Route::prefix('employee')->group(function () {
        Route::get('/documents', [EmployeeController::class, 'documents']); // List documents
        Route::post('/documents', [EmployeeController::class, 'uploadDocuments']); // Upload documents
        Route::delete('/documents/{id}', [EmployeeController::class, 'deleteDocument']); // Delete document
        Route::get('/documents/{id}/download', [EmployeeController::class, 'downloadDocument']); // Download document
        Route::post('/profile-pic', [EmployeeController::class, 'uploadProfilePic']); // Upload profile picture
    });
});