<?php

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
use App\Http\Controllers\Api\Business\BusinessController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\ShiftAssignmentController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ChatMessageController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\CountryController; 

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// CSRF Cookie route (must be public)
Route::middleware(['web'])->group(function () {
    Route::get('/sanctum/csrf-cookie', function () {
        return response()->json(['message' => 'CSRF cookie set']);
    })->name('sanctum.csrf-cookie');
});

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

    /*
    |--------------------------------------------------------------------------
    | Global Resource & Report Routes (Fix for AdminReports.vue 404s)
    |--------------------------------------------------------------------------
    | These routes match the exact API calls from the Vue component.
    | We wrap them in role:admin to ensure security.
    */
    Route::middleware(['role:admin'])->group(function () {
        // Business List for Dropdowns
        Route::get('/businesses', [BusinessController::class, 'index']);
        
        // Country List for Dropdowns
        Route::get('/countries', [CountryController::class, 'index']);
        
        // Department List
        Route::get('/departments', [EmployeeController::class, 'departments']);
        
        // Organization Stats (Top Dashboard Cards)
        Route::get('/org-stats', [ReportController::class, 'getAdminStats']);
        
        // Previously Generated Reports List
        Route::get('/generated-reports', [ReportController::class, 'getGeneratedReports']);
    });

    /*
    |--------------------------------------------------------------------------
    | Employee Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:employee'])->prefix('employee')->group(function () {
      
        // Attendance Routes - Employee Personal
        Route::prefix('attendance')->group(function () {
            Route::get('today-status', [AttendanceController::class, 'todayStatus']);
            Route::get('stats', [AttendanceController::class, 'summary']);
            Route::get('history', [AttendanceController::class, 'history']); // Personal history
            Route::get('/', [AttendanceController::class, 'history']); // Same as above
            Route::post('clock-in', [AttendanceController::class, 'clockIn']);
            Route::post('clock-out', [AttendanceController::class, 'clockOut']);
            Route::post('force-reset', [AttendanceController::class, 'forceReset']);
            Route::get('monthly-stats', [AttendanceController::class, 'monthlyStats']);
            Route::get('monthly-breakdown', [AttendanceController::class, 'monthlyBreakdown']);
            Route::post('recalculate-hours', [AttendanceController::class, 'recalculateHours']);
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
        // Manager's Personal Attendance
        Route::prefix('attendance')->group(function () {
            Route::get('today-status', [AttendanceController::class, 'todayStatus']);
            Route::get('stats', [AttendanceController::class, 'summary']);
            Route::get('history', [AttendanceController::class, 'history']); 
            Route::get('/', [AttendanceController::class, 'history']); 
            Route::post('clock-in', [AttendanceController::class, 'clockIn']);
            Route::post('clock-out', [AttendanceController::class, 'clockOut']);
            Route::post('force-reset', [AttendanceController::class, 'forceReset']);
            Route::get('monthly-stats', [AttendanceController::class, 'monthlyStats']);
            Route::get('monthly-breakdown', [AttendanceController::class, 'monthlyBreakdown']);
            Route::get('/attendance', [AttendanceController::class, 'getManagerTeamAttendance']);
    
            // Get real-time team status (clocked in/out)
            Route::get('/team-status', [AttendanceController::class, 'getTeamStatus']);
            
            // Get employee history
            Route::get('/employee/{employee}', [AttendanceController::class, 'employeeHistory']);
            
            // Get full team history
            Route::get('/team-history', [AttendanceController::class, 'managerTeamHistory']);
        });
        
        // Employee Management
        Route::get('employees', [ManagerController::class, 'employees']);
        Route::get('employees/{employee}', [ManagerController::class, 'employeeDetails']);
      
        // Team Attendance Monitoring
        Route::prefix('attendance')->group(function () {
            // Team reports
            Route::get('team-status', [ManagerController::class, 'attendanceReport']);
            Route::get('team-history', [AttendanceController::class, 'managerTeamHistory']);
            
            // Specific employee attendance
            Route::get('{employee}/history', [AttendanceController::class, 'employeeHistory']);
            Route::get('history/{date}', [AttendanceController::class, 'historyByDate']);
            
            // Mark present for team members
            Route::post('{employee}/mark-present', [AttendanceController::class, 'markPresent']);
        });
        
        // Personal Payslip Routes for Managers
        Route::prefix('payslips')->group(function () {
            Route::get('/', [PayslipController::class, 'index']); // Manager's own payslips
            Route::get('/{payslip}', [PayslipController::class, 'show']);
            Route::get('/{payslip}/download', [PayslipController::class, 'download']);
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
        // Leave Management
        Route::get('/leaves/current-month', [LeaveController::class, 'currentMonthLeaves']);
        
        // Admin's Personal Attendance
        Route::prefix('attendance')->group(function () {
            Route::get('today-status', [AttendanceController::class, 'todayStatus']);
            Route::get('stats', [AttendanceController::class, 'summary']);
            Route::get('history', [AttendanceController::class, 'history']); 
            Route::get('/', [AttendanceController::class, 'history']);
            Route::post('clock-in', [AttendanceController::class, 'clockIn']);
            Route::post('clock-out', [AttendanceController::class, 'clockOut']);
            Route::post('force-reset', [AttendanceController::class, 'forceReset']);
            Route::get('monthly-stats', [AttendanceController::class, 'monthlyStats']);
            Route::get('monthly-breakdown', [AttendanceController::class, 'monthlyBreakdown']);
        });
        
        // Employee Management
        Route::apiResource('employees', EmployeeController::class);
        Route::get('managers', [EmployeeController::class, 'managers']);
        
        // Attendance Management - System-wide
        Route::prefix('attendance')->group(function () {
            // Main attendance monitor endpoint
            Route::get('status', [AttendanceController::class, 'getAttendanceStatus']);
            
            // Admin history with filters
            Route::get('history', [AttendanceController::class, 'adminHistory']);
            
            // Mark attendance
            Route::post('{employee}/mark-present', [AttendanceController::class, 'markPresent']);
            Route::post('bulk-mark-present', [AttendanceController::class, 'bulkMarkPresent']);
            
            // Current statuses
            Route::get('current-statuses', [AttendanceController::class, 'currentStatuses']);
            
            // Filter options
            Route::get('countries', [AttendanceController::class, 'getCountries']);
            Route::get('businesses', [AttendanceController::class, 'getBusinesses']);
            
            // Recalculate hours
            Route::post('recalculate-hours', [AttendanceController::class, 'recalculateHours']);
        });
        
        // Department and Country Filters (Admin specific namespace)
        Route::get('/departments', [EmployeeController::class, 'departments']);
        
        // Report Generation
        Route::post('generate/attendance', [ReportController::class, 'generateAttendanceReport']);
        Route::post('generate/leave', [ReportController::class, 'generateLeaveReport']);
        Route::post('generate/payroll', [ReportController::class, 'generatePayrollReport']);
        Route::post('generate/organization', [ReportController::class, 'generateOrganizationReport']);
        Route::post('download/{type}', [ReportController::class, 'downloadReport']);
      
        // Payroll Management
        Route::prefix('payroll')->group(function () {
            Route::post('process', [PayrollController::class, 'processPayroll']);
            Route::get('employees-summary', [PayrollController::class, 'employeesSummary']); 
            Route::post('preview', [PayrollController::class, 'preview']);
            Route::get('history', [PayrollController::class, 'history']);
            Route::get('cycles', [PayrollController::class, 'cycles']);
            Route::post('/update-status', [PayrollController::class, 'updateStatus']);
            Route::get('/{payroll}', [PayrollController::class, 'show']);
            Route::delete('/{payroll}', [PayrollController::class, 'destroy']);
            Route::get('employee/{employeeId}/payslip', [PayrollController::class, 'viewEmployeePayslip']);
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
        
        // Get all tax configurations for a specific country
        Route::get('/tax-configurations/country/{countryCode}', [AdminController::class, 'getTaxConfigurationsByCountry']);
        
        // Save/update tax configuration
        Route::post('/update-tax-configuration', [AdminController::class, 'updateTaxConfiguration']);
        
        // Delete tax configuration
        Route::delete('/tax-configuration/{id}', [AdminController::class, 'deleteTaxConfiguration']);

        // Reports (Nested under /api/admin/reports)
        Route::prefix('reports')->group(function () {
            // Stats and basic data
            Route::get('stats', [ReportController::class, 'getAdminStats']);
            Route::get('report-params/{type}', [ReportController::class, 'getReportParams']);
            Route::get('generated-reports', [ReportController::class, 'getGeneratedReports']);
            
            // Report generation endpoints
            Route::post('generate/attendance', [ReportController::class, 'generateAttendanceReport']);
            Route::post('generate/leave', [ReportController::class, 'generateLeaveReport']);
            Route::post('generate/payroll', [ReportController::class, 'generatePayrollReport']);
                Route::post('generate/earnings', [ReportController::class, 'generateEarningsReport']);  // ADD THIS
    Route::post('generate/deductions', [ReportController::class, 'generateDeductionsReport']);
            Route::post('generate/organization', [ReportController::class, 'generateOrganizationReport']);
            Route::post('download/{type}', [ReportController::class, 'downloadReport']);
            
            // Predefined report views
            Route::get('payroll', [ReportController::class, 'payrollReport']);
            Route::get('attendance', [ReportController::class, 'attendanceReport']);
            Route::get('leave', [ReportController::class, 'leaveReport']);
            
            // Dynamic Report Types (matching the Vue component dynamic calls)
            Route::post('/attendance', [ReportController::class, 'generateAttendanceReport']);
            Route::post('/leave', [ReportController::class, 'generateLeaveReport']);
            Route::post('/payroll', [ReportController::class, 'generatePayrollReport']);
            Route::post('/earnings', [ReportController::class, 'generateEarningsReport']); // Assuming method exists
            Route::post('/deductions', [ReportController::class, 'generateDeductionsReport']); // Assuming method exists
            Route::get('/{reportId}/download/pdf', [ReportController::class, 'downloadPdf']);
            Route::get('/{reportId}/export/excel', [ReportController::class, 'exportExcel']);
        });
      
        // System Management
        Route::get('audit-logs', [AdminController::class, 'auditLogs']);
        Route::get('settings', [AdminController::class, 'getSettings']);
        Route::put('settings', [AdminController::class, 'updateSettings']);
        Route::get('/countries-with-settings', [AdminController::class, 'getCountriesWithSettings']);
        Route::get('/available-countries', [AdminController::class, 'getAvailableCountries']);
        Route::post('/country-settings/initialize', [AdminController::class, 'initializeCountrySettings']);
        Route::delete('/country-settings/{countryCode}', [AdminController::class, 'deleteCountrySettings']);
        Route::get('/businesses-with-countries', [AdminController::class, 'getBusinessesWithCountries']);
        Route::post('/business-settings/initialize', [AdminController::class, 'initializeBusinessSettings']);
        Route::delete('/business-settings/{businessId}', [AdminController::class, 'deleteBusinessSettings']);
        Route::get('/stats', [AdminController::class, 'systemStats']);
    });
});

// Task Management Routes
Route::middleware(['auth:sanctum'])->group(function () {
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

// Profile and Document Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/profile', [EmployeeController::class, 'profile']);
    Route::put('/profile', [EmployeeController::class, 'updateProfile']);
    Route::post('/profile/password', [EmployeeController::class, 'updatePassword']);
    
    // Employee Documents Routes (Available to all authenticated users with employee record)
    Route::prefix('employee')->group(function () {
        Route::get('/documents', [EmployeeController::class, 'documents']); // List documents
        Route::post('/documents', [EmployeeController::class, 'uploadDocuments']); // Upload documents
        Route::delete('/documents/{id}', [EmployeeController::class, 'deleteDocument']); // Delete document
        Route::get('/documents/{id}/download', [EmployeeController::class, 'downloadDocument']); // Download document
        Route::post('/profile-pic', [EmployeeController::class, 'uploadProfilePic']); // Upload profile picture
    });
});

/*
|--------------------------------------------------------------------------
| Country Management Routes (Admin Only)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Country CRUD operations
    Route::prefix('countries')->group(function () {
        Route::get('/', [CountryController::class, 'index']);
        Route::post('/', [CountryController::class, 'store']);
        Route::get('/{country}', [CountryController::class, 'show']);
        Route::put('/{country}', [CountryController::class, 'update']);
        Route::delete('/{country}', [CountryController::class, 'destroy']);
        
        // Additional country operations
        Route::post('/{country}/toggle-status', [CountryController::class, 'toggleStatus']);
        Route::get('/{country}/statistics', [CountryController::class, 'statistics']);
    });
    
    // Business Routes
    Route::prefix('businesses')->group(function () {
        Route::get('/', [BusinessController::class, 'index']);
        Route::post('/', [BusinessController::class, 'store']);
        Route::get('/{business}', [BusinessController::class, 'show']);
        Route::put('/{business}', [BusinessController::class, 'update']);
        Route::post('/{business}/switch', [BusinessController::class, 'switchBusiness']);
        Route::delete('/{business}', [BusinessController::class, 'destroy']);
    });
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('settings')->group(function () {
        Route::get('departments', [SettingsController::class, 'getValidDepartments']);
        Route::get('system', [SettingsController::class, 'getSystemSettings']);
        Route::post('validate-department', [SettingsController::class, 'validateDepartment']);
    });
});

// Schedule CRUD routes
Route::prefix('schedules')->group(function () {
    Route::get('/', [ScheduleController::class, 'index']);
    Route::post('/', [ScheduleController::class, 'store']);
    Route::get('/{id}', [ScheduleController::class, 'show']);
    Route::put('/{id}', [ScheduleController::class, 'update']);
    Route::delete('/{id}', [ScheduleController::class, 'destroy']);
    Route::post('/{id}/complete', [ScheduleController::class, 'complete']);
    Route::put('/{id}/meta', [ScheduleController::class, 'updateMeta']);
    
    // Calendar data
    Route::get('/calendar/data', [ScheduleController::class, 'getCalendarData']);
});

// Employee schedule routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/employee/schedules', [ScheduleController::class, 'getEmployeeSchedules']);
});

// Notification routes
Route::prefix('notifications')->group(function () {
    Route::get('/', [ScheduleController::class, 'getNotifications']);
    Route::put('/{id}/read', [ScheduleController::class, 'markNotificationRead']);
    Route::post('/read-all', [ScheduleController::class, 'markAllNotificationsRead']);
});

// Shift Assignments
Route::middleware(['auth:sanctum'])->group(function () {
    // Employee routes
    Route::get('/shift-assignments/my-shifts', [ShiftAssignmentController::class, 'myShifts']);
    Route::get('/shift-assignments/today', [ShiftAssignmentController::class, 'todayShift']);
    Route::post('/shift-assignments/{id}/accept', [ShiftAssignmentController::class, 'accept']);
    Route::post('/shift-assignments/{id}/reject', [ShiftAssignmentController::class, 'reject']);
    
    Route::get('/shift-assignments/assigned-by-me', [ShiftAssignmentController::class, 'assignedByMe']);
    Route::get('/shift-assignments', [ShiftAssignmentController::class, 'index']);
    Route::post('/shift-assignments', [ShiftAssignmentController::class, 'store']);
    Route::post('/shift-assignments/bulk', [ShiftAssignmentController::class, 'bulkStore']);
    Route::put('/shift-assignments/{id}', [ShiftAssignmentController::class, 'update']);
    Route::post('/shift-assignments/{id}/cancel', [ShiftAssignmentController::class, 'cancel']);
    Route::delete('/shift-assignments/{id}', [ShiftAssignmentController::class, 'destroy']);
});

Route::middleware(['auth:sanctum'])->prefix('chat')->group(function () {
    // Group management
    Route::get('/groups', [ChatController::class, 'index']);
    Route::post('/groups', [ChatController::class, 'store']);
    Route::get('/groups/{id}', [ChatController::class, 'show']);
    Route::put('/groups/{id}', [ChatController::class, 'update']);
    Route::delete('/groups/{id}', [ChatController::class, 'destroy']);
    
    // Group members
    Route::post('/groups/{id}/members', [ChatController::class, 'addMembers']);
    Route::delete('/groups/{id}/members/{userId}', [ChatController::class, 'removeMember']);
    Route::get('/groups/{id}/members', [ChatController::class, 'getMembers']);
    
    // Direct messaging
    Route::post('/direct-message', [ChatController::class, 'getOrCreateDirectMessage']);
    
    // Available users for chat
    Route::get('/available-users', [ChatController::class, 'getAvailableUsers']);
    
    // Group actions
    Route::post('/groups/{id}/mute', [ChatController::class, 'toggleMute']);
    Route::post('/groups/{id}/read', [ChatController::class, 'markAsRead']);
    Route::post('/groups/{id}/leave', [ChatController::class, 'leaveGroup']);
    
    // Messages
    Route::get('/groups/{groupId}/messages', [ChatMessageController::class, 'index']);
    Route::post('/groups/{groupId}/messages', [ChatMessageController::class, 'store']);
    Route::put('/groups/{groupId}/messages/{messageId}', [ChatMessageController::class, 'update']);
    Route::delete('/groups/{groupId}/messages/{messageId}', [ChatMessageController::class, 'destroy']);
    Route::get('/groups/{groupId}/messages/search', [ChatMessageController::class, 'search']);
    
    // Message reactions
    Route::post('/messages/{messageId}/reactions', [ChatMessageController::class, 'addReaction']);
    Route::delete('/messages/{messageId}/reactions', [ChatMessageController::class, 'removeReaction']);
});

// Shift Management Routes
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Manager/Admin routes
    Route::prefix('shifts')->group(function () {
        Route::get('available-employees', [ShiftAssignmentController::class, 'getAvailableEmployees']);
        Route::post('assign', [ShiftAssignmentController::class, 'assignShift']);
        Route::post('bulk-assign', [ShiftAssignmentController::class, 'bulkAssignShifts']);
        Route::get('check-availability', [ShiftAssignmentController::class, 'checkAvailability']);
    });

    // Employee routes
    Route::prefix('shifts')->group(function () {
        Route::get('my-shifts', [ShiftAssignmentController::class, 'getMyShifts']);
        Route::post('{shift}/accept', [ShiftAssignmentController::class, 'acceptShift']);
        Route::post('{shift}/reject', [ShiftAssignmentController::class, 'rejectShift']);
    });
});

Route::middleware(['auth:sanctum'])->prefix('tickets')->group(function () {
    // Specific routes first (before the parameter routes)
    Route::get('/count', [TicketController::class, 'count'])->name('tickets.count');
    Route::get('/stats', [TicketController::class, 'stats'])->name('tickets.stats');
    Route::get('/my-tickets', [TicketController::class, 'myTickets'])->name('tickets.my');
    
    // Standard CRUD routes (parameter routes come last)
    Route::get('/', [TicketController::class, 'index'])->name('tickets.index');
    Route::post('/', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::put('/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
    Route::delete('/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');
    
    // Additional actions
    Route::post('/{ticket}/assign', [TicketController::class, 'assign'])->name('tickets.assign');
    Route::post('/{ticket}/resolve', [TicketController::class, 'resolve'])->name('tickets.resolve');
    Route::post('/{ticket}/close', [TicketController::class, 'close'])->name('tickets.close');
    Route::post('/{ticket}/reopen', [TicketController::class, 'reopen'])->name('tickets.reopen');
});