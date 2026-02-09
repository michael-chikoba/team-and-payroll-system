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
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SubtaskController;
use App\Http\Controllers\Api\TaskWorkLogController;
use App\Http\Controllers\Api\TaskLinkController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\ChatReactionController;
use App\Http\Controllers\Api\ChatThreadController;
use App\Http\Controllers\Api\ChatIntegrationController;
use App\Http\Controllers\Api\ChatWebhookController;
use App\Http\Controllers\Api\TaskReportController;
use App\Http\Controllers\Api\ActivityTrackingController;
use App\Http\Controllers\Api\NotificationChannelController;
use App\Http\Controllers\Api\SlackIntegrationController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\ProductivityController;
use App\Http\Controllers\Api\SuperAdmin\{
    SuperAdminBusinessController,
    SuperAdminUserController,
    SuperAdminAnalyticsController,
    SuperAdminSettingsController
};
use App\Http\Controllers\Api\BusinessGroupController;
use App\Http\Controllers\Api\BusinessGroupMembershipController;
use App\Http\Controllers\Api\BusinessGroupInvitationController;
use App\Http\Controllers\Api\GroupTicketController;
use App\Http\Controllers\Api\GroupTaskController;



// Public routes (no authentication required)
Route::post('/demo-requests', [PublicController::class, 'storeDemoRequest']);
Route::post('/contact', [PublicController::class, 'storeContactRequest']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/admin/reports/productivity', [ProductivityController::class, 'adminIndex']);
    Route::get('/manager/reports/productivity', [ProductivityController::class, 'managerIndex']);
    Route::get('/productivity', [ProductivityController::class, 'index']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    // Demo Requests Management
    Route::get('/admin/demo-requests', [PublicController::class, 'getDemoRequests']);
    Route::get('/admin/demo-requests/{id}', [PublicController::class, 'getDemoRequest']);
    Route::put('/admin/demo-requests/{id}', [PublicController::class, 'updateDemoRequest']);
    Route::delete('/admin/demo-requests/{id}', [PublicController::class, 'deleteDemoRequest']);
    
    // Contact Requests Management
    Route::get('/admin/contact-requests', [PublicController::class, 'getContactRequests']);
    Route::get('/admin/contact-requests/{id}', [PublicController::class, 'getContactRequest']);
    Route::put('/admin/contact-requests/{id}', [PublicController::class, 'updateContactRequest']);
    Route::delete('/admin/contact-requests/{id}', [PublicController::class, 'deleteContactRequest']);
});
/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/
// ADD THIS ROUTE - It must be public (no auth middleware)
// Simple version
Route::get('/sanctum/csrf-cookie', function () {
    return response()->noContent();
});

// Slack Integration Routes (Admin only)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/slack-integration', [SlackIntegrationController::class, 'index']);
    Route::post('/slack-integration', [SlackIntegrationController::class, 'store']);
    Route::put('/slack-integration/{slackIntegration}', [SlackIntegrationController::class, 'update']);
    Route::delete('/slack-integration/{slackIntegration}', [SlackIntegrationController::class, 'delete']);
    Route::post('/slack-integration/{slackIntegration}/test', [SlackIntegrationController::class, 'test']);
    Route::get('/slack-integration/{slackIntegration}/logs', [SlackIntegrationController::class, 'logs']);
});
//public login routes
Route::post('/login', [AuthController::class, 'login'])->name('login');
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
        Route::get('/debug-token', [AuthController::class, 'debugToken']); // ADD THIS
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
    Route::get('/auth/login-history', [AuthController::class, 'loginHistory']);
   
    // Dashboard (Available to all authenticated users)
    Route::get('/dashboard', [DashboardController::class, 'index']);

      /*
    |--------------------------------------------------------------------------
    | Shared Resources (Accessible by ID)
    |--------------------------------------------------------------------------
    | These must be placed carefully to avoid conflicting with specific routes.
    */
    Route::get('/leaves/{leave}', [LeaveController::class, 'show'])->whereNumber('leave');
    /*
    |--------------------------------------------------------------------------
    | Notification Routes (Available to ALL authenticated users)
    |--------------------------------------------------------------------------
    */
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
       Route::get('/business-group-invitations', [BusinessGroupInvitationController::class, 'index']);

    });
    Route::middleware(['auth:sanctum'])->group(function () {
    
    // Notification Channel Management
    Route::prefix('notification-channels')->group(function () {
        Route::get('/', [NotificationChannelController::class, 'index']);
        Route::post('/', [NotificationChannelController::class, 'store']);
        Route::put('/{channel}', [NotificationChannelController::class, 'update']);
        Route::delete('/{channel}', [NotificationChannelController::class, 'destroy']);
        
        // Helper endpoints
        Route::get('/available-chat-groups', [NotificationChannelController::class, 'getAvailableChatGroups']);
        Route::get('/available-events', [NotificationChannelController::class, 'getAvailableEvents']);
        
        // Test and logs
        Route::post('/{channel}/test', [NotificationChannelController::class, 'test']);
        Route::get('/{channel}/logs', [NotificationChannelController::class, 'getLogs']);
    });
});

    /*
    |--------------------------------------------------------------------------
    | Ticket Management Routes (Available to all authenticated users)
    |--------------------------------------------------------------------------
    */
    
  Route::prefix('tickets')->group(function () {
    // Ticket types and metadata
    Route::get('/types', [TicketController::class, 'getTicketTypes']);
    Route::get('/types/{type}/categories', [TicketController::class, 'getCategories']);
    
    // Departments for ticket assignment
    Route::get('/departments', [TicketController::class, 'getDepartments']);
    Route::get('/assigned-to-me', [TicketController::class, 'assignedTickets']);
    // Assignable users and approvers
    Route::get('/assignable-users', [TicketController::class, 'getAssignableUsers']);
    Route::get('/approvers', [TicketController::class, 'getApprovers']);
    
    // Statistics and counts
    Route::get('/statistics', [TicketController::class, 'statistics']);
    Route::get('/count', [TicketController::class, 'count']);
    
    // User-specific tickets
    Route::get('/my-tickets', [TicketController::class, 'myTickets']);
   // Route::get('/assigned-to-me', [TicketController::class, 'assignedToMe']);
    
    // CRUD operations
    Route::get('/', [TicketController::class, 'index']);
    Route::post('/', [TicketController::class, 'store']);
    Route::get('/{ticket}', [TicketController::class, 'show']);
    Route::put('/{ticket}', [TicketController::class, 'update']);
    Route::delete('/{ticket}', [TicketController::class, 'destroy']);
    
    // Ticket actions - FIXED ROUTES
    Route::post('/{ticket}/update-status', [TicketController::class, 'updateStatus']);
    Route::patch('/{ticket}/priority', [TicketController::class, 'updatePriority']); // ✅ FIXED: Changed from /update-priority to /priority
    Route::post('/{ticket}/reassign', [TicketController::class, 'reassignTicket']);
    Route::post('/{ticket}/assign', [TicketController::class, 'assignTicket']);
    
    // Comments and attachments
    Route::get('/{ticket}/comments', [TicketController::class, 'getComments']);
    Route::post('/{ticket}/comments', [TicketController::class, 'addComment']);
    Route::put('/{ticket}/comments/{comment}', [TicketController::class, 'updateComment']);
    Route::delete('/{ticket}/comments/{comment}', [TicketController::class, 'deleteComment']);
    
    Route::get('/{ticket}/attachments', [TicketController::class, 'getAttachments']);
    Route::post('/{ticket}/attachments', [TicketController::class, 'uploadAttachment']);
    Route::delete('/{ticket}/attachments/{attachment}', [TicketController::class, 'deleteAttachment']);
    Route::get('/attachments/{attachment}/download', [TicketController::class, 'downloadAttachment'])->name('tickets.attachments.download');
    
    // Activity history
    Route::get('/{ticket}/activities', [TicketController::class, 'getActivityHistory']);
    
    // Convenience routes (optional)
    Route::post('/{ticket}/resolve', [TicketController::class, 'resolve']);
    Route::post('/{ticket}/close', [TicketController::class, 'close']);
    Route::post('/{ticket}/reopen', [TicketController::class, 'reopen']);
});

    /*
    |--------------------------------------------------------------------------
    | Global Resource & Report Routes (Fix for AdminReports.vue 404s)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/businesses', [BusinessController::class, 'index']);
        Route::get('/countries', [CountryController::class, 'index']);
        Route::get('/departments', [EmployeeController::class, 'departments']);
        Route::get('/org-stats', [ReportController::class, 'getAdminStats']);
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
            Route::get('history', [AttendanceController::class, 'history']);
            Route::get('/', [AttendanceController::class, 'history']);
            Route::post('clock-in', [AttendanceController::class, 'clockIn']);
            Route::post('clock-out', [AttendanceController::class, 'clockOut']);
            Route::post('clock-in-overtime', [AttendanceController::class, 'clockInOvertime']);
            Route::post('force-reset', [AttendanceController::class, 'forceReset']);
            Route::get('monthly-stats', [AttendanceController::class, 'monthlyStats']);
            Route::get('monthly-breakdown', [AttendanceController::class, 'monthlyBreakdown']);
            Route::get('overtime-summary', [AttendanceController::class, 'overtimeSummary']);
            Route::get('detailed-history', [AttendanceController::class, 'detailedHistory']);
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
            Route::get('/', [PayslipController::class, 'index']);
            Route::get('/{payslip}', [PayslipController::class, 'show']);
            Route::get('/{payslip}/download', [PayslipController::class, 'download']);
        });
      
        Route::post('initialize-leave-balances', [EmployeeController::class, 'initializeLeaveBalances']);
    });
   
    /*
    |--------------------------------------------------------------------------
    | Manager Routes - FIXED ORDER
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:manager'])->prefix('manager')->group(function () {
        
        // Employee Management
        Route::get('employees', [ManagerController::class, 'employees']);
        Route::get('employees/{employee}', [ManagerController::class, 'employeeDetails']);
        
        // Manager's Personal Attendance (specific routes first)
        Route::prefix('attendance')->group(function () {
            // SPECIFIC ROUTES FIRST (before generic routes)
            Route::get('team-status', [AttendanceController::class, 'getTeamStatus']);
            Route::get('team-history', [AttendanceController::class, 'managerTeamHistory']);
            Route::get('today-status', [AttendanceController::class, 'todayStatus']);
            Route::get('stats', [AttendanceController::class, 'summary']);
            Route::get('monthly-stats', [AttendanceController::class, 'monthlyStats']);
            Route::get('monthly-breakdown', [AttendanceController::class, 'monthlyBreakdown']);
            Route::get('overtime-summary', [AttendanceController::class, 'overtimeSummary']);
            Route::get('detailed-history', [AttendanceController::class, 'detailedHistory']);
            
            // Employee-specific routes (with parameter)
            Route::get('{employee}/history', [AttendanceController::class, 'employeeHistory']);
            Route::post('{employee}/mark-present', [AttendanceController::class, 'markPresent']);
            Route::post('{employee}/clock-out', [AttendanceController::class, 'managerClockOut']);
            
            // Date-based history
            Route::get('history/{date}', [AttendanceController::class, 'historyByDate']);
            
            // Personal attendance actions
            Route::get('history', [AttendanceController::class, 'history']);
            Route::get('/', [AttendanceController::class, 'history']);
            Route::post('clock-in', [AttendanceController::class, 'clockIn']);
            Route::post('clock-out', [AttendanceController::class, 'clockOut']);
            Route::post('clock-in-overtime', [AttendanceController::class, 'clockInOvertime']);
            Route::post('force-reset', [AttendanceController::class, 'forceReset']);
            Route::post('recalculate-hours', [AttendanceController::class, 'recalculateHours']);
        });
        
        // Personal Payslip Routes for Managers
        Route::prefix('payslips')->group(function () {
            Route::get('/', [PayslipController::class, 'index']);
            Route::get('/{payslip}', [PayslipController::class, 'show']);
            Route::get('/{payslip}/download', [PayslipController::class, 'download']);
        });
        
        // Leave Management
        Route::prefix('leaves')->group(function () {
            Route::get('pending', [LeaveController::class, 'pendingLeaves']);
            Route::post('{leave}/approve', [LeaveController::class, 'approve']);
            Route::post('{leave}/reject', [LeaveController::class, 'reject']);
            // ADDED: Missing route for current-month leaves (fixes 404 for managers)
            Route::get('current-month', [LeaveController::class, 'currentMonthLeaves']);
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
        // Admin Audit Routes
        Route::prefix('audit')->group(function () {
            Route::get('logins', [AuditController::class, 'loginAudits']);
            Route::get('login-stats', [AuditController::class, 'loginStats']);
            Route::get('user/{userId}/logins', [AuditController::class, 'userLoginHistory']);
        });
        
        // Leave Management
        Route::get('/leaves/current-month', [LeaveController::class, 'currentMonthLeaves']);
         Route::post('/leaves/{leave}/approve', [LeaveController::class, 'approve']);  // ADD THIS LINE
    Route::post('/leaves/{leave}/reject', [LeaveController::class, 'reject']);    // ADD THIS LINE
        
        // Admin's Personal Attendance
        Route::prefix('attendance')->group(function () {
            // Specific routes first
            Route::get('status', [AttendanceController::class, 'getAttendanceStatus']);
            Route::get('current-statuses', [AttendanceController::class, 'currentStatuses']);
            Route::get('countries', [AttendanceController::class, 'getCountries']);
            Route::get('businesses', [AttendanceController::class, 'getBusinesses']);
            Route::get('today-status', [AttendanceController::class, 'todayStatus']);
            Route::get('stats', [AttendanceController::class, 'summary']);
            Route::get('monthly-stats', [AttendanceController::class, 'monthlyStats']);
            Route::get('monthly-breakdown', [AttendanceController::class, 'monthlyBreakdown']);
            Route::get('overtime-summary', [AttendanceController::class, 'overtimeSummary']);
            Route::get('detailed-history', [AttendanceController::class, 'detailedHistory']);
            
            // Actions
            Route::post('bulk-mark-present', [AttendanceController::class, 'bulkMarkPresent']);
            Route::post('recalculate-hours', [AttendanceController::class, 'recalculateHours']);
            Route::post('{employee}/mark-present', [AttendanceController::class, 'markPresent']);
            
            // Personal actions
            Route::get('history', [AttendanceController::class, 'adminHistory']);
            Route::get('/', [AttendanceController::class, 'history']);
            Route::post('clock-in', [AttendanceController::class, 'clockIn']);
            Route::post('clock-out', [AttendanceController::class, 'clockOut']);
            Route::post('clock-in-overtime', [AttendanceController::class, 'clockInOvertime']);
            Route::post('force-reset', [AttendanceController::class, 'forceReset']);
        });
        
        // Employee Management
        Route::apiResource('employees', EmployeeController::class);
        Route::get('managers', [EmployeeController::class, 'managers']);
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
      
        // Payslip Management
        Route::prefix('payslips')->group(function () {
            Route::get('/', [PayslipController::class, 'adminIndex']);
            Route::post('/', [PayslipController::class, 'store']);
            Route::get('/{payslip}', [PayslipController::class, 'show']);
            Route::post('/generate', [PayslipController::class, 'generate']);
            Route::post('/bulk-download', [PayslipController::class, 'bulkDownload']);
            Route::post('/{payslip}/generate-pdf', [PayslipController::class, 'generatePdf']);
            Route::get('/{payslip}/download', [PayslipController::class, 'download']);
            Route::post('/{payslip}/send', [PayslipController::class, 'sendNotifications']);
        });
      
        // Tax Configuration
        Route::get('tax-configuration', [AdminController::class, 'getTaxConfiguration']);
        Route::get('/tax-configurations/country/{countryCode}', [AdminController::class, 'getTaxConfigurationsByCountry']);
        Route::post('/update-tax-configuration', [AdminController::class, 'updateTaxConfiguration']);
        Route::delete('/tax-configuration/{id}', [AdminController::class, 'deleteTaxConfiguration']);

        // Reports
        Route::prefix('reports')->group(function () {
            Route::get('stats', [ReportController::class, 'getAdminStats']);
            Route::get('report-params/{type}', [ReportController::class, 'getReportParams']);
            Route::get('generated-reports', [ReportController::class, 'getGeneratedReports']);
            Route::post('generate/attendance', [ReportController::class, 'generateAttendanceReport']);
            Route::post('generate/leave', [ReportController::class, 'generateLeaveReport']);
            Route::post('generate/payroll', [ReportController::class, 'generatePayrollReport']);
            Route::post('generate/earnings', [ReportController::class, 'generateEarningsReport']);
            Route::post('generate/deductions', [ReportController::class, 'generateDeductionsReport']);
            Route::post('generate/organization', [ReportController::class, 'generateOrganizationReport']);
            Route::post('download/{type}', [ReportController::class, 'downloadReport']);
            Route::get('payroll', [ReportController::class, 'payrollReport']);
            Route::get('attendance', [ReportController::class, 'attendanceReport']);
            Route::get('leave', [ReportController::class, 'leaveReport']);
            Route::post('/attendance', [ReportController::class, 'generateAttendanceReport']);
            Route::post('/leave', [ReportController::class, 'generateLeaveReport']);
            Route::post('/payroll', [ReportController::class, 'generatePayrollReport']);
            Route::post('/earnings', [ReportController::class, 'generateEarningsReport']);
            Route::post('/deductions', [ReportController::class, 'generateDeductionsReport']);
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
        Route::get('accessible-businesses', [AdminController::class, 'getAccessibleBusinesses']);

        // Country Management
        Route::prefix('countries')->group(function () {
            Route::get('/', [CountryController::class, 'index']);
            Route::post('/', [CountryController::class, 'store']);
            Route::get('/{country}', [CountryController::class, 'show']);
            Route::put('/{country}', [CountryController::class, 'update']);
            Route::delete('/{country}', [CountryController::class, 'destroy']);
            Route::post('/{country}/toggle-status', [CountryController::class, 'toggleStatus']);
            Route::get('/{country}/statistics', [CountryController::class, 'statistics']);
        });
         Route::get('users/search', [EmployeeController::class, 'searchUsers']);
    
      // Business Management Routes
Route::prefix('businesses')->group(function () {
    Route::get('/', [BusinessController::class, 'index']);
    Route::post('/', [BusinessController::class, 'store']);
    Route::get('/current', [BusinessController::class, 'getCurrentBusiness']); // NEW ROUTE
    Route::get('/{business}', [BusinessController::class, 'show']);
    Route::put('/{business}', [BusinessController::class, 'update']);
    Route::post('/{business}/switch', [BusinessController::class, 'switchBusiness']); // ENHANCED
    Route::delete('/{business}', [BusinessController::class, 'destroy']);
    
    // Admin management routes
    Route::post('/{business}/admins', [BusinessController::class, 'addAdmin']);
    Route::delete('/{business}/admins/{adminUser}', [BusinessController::class, 'removeAdmin']);
    Route::put('/{business}/admins/{adminUser}/primary', [BusinessController::class, 'updatePrimaryAdmin']);
    Route::get('/{business}/stats', [BusinessController::class, 'getStats']); // NEW ROUTE
});
    });
});

/*
|--------------------------------------------------------------------------
| Task Management Routes - ALL authenticated users can create tasks
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {
    // Employee list endpoints - accessible by managers and admins
    Route::get('/employees', [TaskController::class, 'getEmployees'])->middleware('role:manager,admin,employee');
    Route::get('/tasks/employees/simple', [TaskController::class, 'getSimpleEmployees'])->middleware('role:manager,admin,employee');
    
    // Task CRUD - ALL authenticated users can access
    Route::get('/tasks', [TaskController::class, 'index']); // All can view their tasks
    Route::post('/tasks', [TaskController::class, 'store']); // All can create tasks (employees can only assign to themselves)
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus']); // All can update status
    Route::get('/tasks/{task}', [TaskController::class, 'show']); // All can view tasks they're involved with
    Route::put('/tasks/{task}', [TaskController::class, 'update']); // All can update their own tasks
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']); // All can delete their own tasks
    
    // Task comments - all authenticated users
    Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store']);
    Route::delete('/comments/{comment}', [TaskCommentController::class, 'destroy']);

    // Subtask routes - all authenticated users
    Route::post('/tasks/{task}/subtasks', [SubtaskController::class, 'store']);
    Route::patch('/subtasks/{subtask}', [SubtaskController::class, 'update']);
    Route::delete('/subtasks/{subtask}', [SubtaskController::class, 'destroy']);

    // Work log routes - all authenticated users
    Route::post('/tasks/{task}/worklogs', [TaskWorkLogController::class, 'store']);
    Route::delete('/worklogs/{workLog}', [TaskWorkLogController::class, 'destroy']);

    // Task link routes - all authenticated users
    Route::post('/tasks/{task}/links', [TaskLinkController::class, 'store']);
    Route::delete('/task-links/{link}', [TaskLinkController::class, 'destroy']);
});

// Profile and Document Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/profile', [EmployeeController::class, 'profile']);
    Route::put('/profile', [EmployeeController::class, 'updateProfile']);
    Route::post('/profile/password', [EmployeeController::class, 'updatePassword']);
    
    Route::prefix('employee')->group(function () {
        Route::get('/documents', [EmployeeController::class, 'documents']);
        Route::post('/documents', [EmployeeController::class, 'uploadDocuments']);
        Route::delete('/documents/{id}', [EmployeeController::class, 'deleteDocument']);
        Route::get('/documents/{id}/download', [EmployeeController::class, 'downloadDocument']);
        Route::post('/profile-pic', [EmployeeController::class, 'uploadProfilePic']);
    });
});

// Settings Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('settings')->group(function () {
        Route::get('departments', [SettingsController::class, 'getValidDepartments']);
        Route::get('system', [SettingsController::class, 'getSystemSettings']);
        Route::post('validate-department', [SettingsController::class, 'validateDepartment']);
    });
});

// Schedule Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('schedules')->group(function () {
        Route::get('/', [ScheduleController::class, 'index']);
        Route::post('/', [ScheduleController::class, 'store']);
        Route::get('/{id}', [ScheduleController::class, 'show']);
        Route::put('/{id}', [ScheduleController::class, 'update']);
        Route::delete('/{id}', [ScheduleController::class, 'destroy']);
        Route::post('/{id}/complete', [ScheduleController::class, 'complete']);
        Route::put('/{id}/meta', [ScheduleController::class, 'updateMeta']);
        Route::get('/calendar/data', [ScheduleController::class, 'getCalendarData']);
    });
    
    Route::get('/employee/schedules', [ScheduleController::class, 'getEmployeeSchedules']);
});

// Shift Assignments
Route::middleware(['auth:sanctum'])->group(function () {
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

// Chat Routes
Route::middleware(['auth:sanctum'])->prefix('chat')->group(function () {
    Route::get('/groups', [ChatController::class, 'index']);
    Route::post('/groups', [ChatController::class, 'store']);
    Route::get('/groups/{id}', [ChatController::class, 'show']);
    Route::put('/groups/{id}', [ChatController::class, 'update']);
    Route::delete('/groups/{id}', [ChatController::class, 'destroy']);
    Route::post('/groups/{id}/members', [ChatController::class, 'addMembers']);
    Route::delete('/groups/{id}/members/{userId}', [ChatController::class, 'removeMember']);
    Route::get('/groups/{id}/members', [ChatController::class, 'getMembers']);
    Route::post('/direct-message', [ChatController::class, 'getOrCreateDirectMessage']);
    Route::get('/available-users', [ChatController::class, 'getAvailableUsers']);
    Route::post('/groups/{id}/mute', [ChatController::class, 'toggleMute']);
    Route::post('/groups/{id}/read', [ChatController::class, 'markAsRead']);
    Route::post('/groups/{id}/leave', [ChatController::class, 'leaveGroup']);
    Route::get('/groups/{groupId}/messages', [ChatMessageController::class, 'index']);
    Route::post('/groups/{groupId}/messages', [ChatMessageController::class, 'store']);
    Route::put('/groups/{groupId}/messages/{messageId}', [ChatMessageController::class, 'update']);
    Route::delete('/groups/{groupId}/messages/{messageId}', [ChatMessageController::class, 'destroy']);
    Route::get('/groups/{groupId}/messages/search', [ChatMessageController::class, 'search']);
    Route::post('/messages/{messageId}/reactions', [ChatMessageController::class, 'addReaction']);
    Route::delete('/messages/{messageId}/reactions', [ChatMessageController::class, 'removeReaction']);
});

// Shift Management
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('shifts')->group(function () {
        Route::get('available-employees', [ShiftAssignmentController::class, 'getAvailableEmployees']);
        Route::post('assign', [ShiftAssignmentController::class, 'assignShift']);
        Route::post('bulk-assign', [ShiftAssignmentController::class, 'bulkAssignShifts']);
        Route::get('check-availability', [ShiftAssignmentController::class, 'checkAvailability']);
        Route::get('my-shifts', [ShiftAssignmentController::class, 'getMyShifts']);
        Route::post('{shift}/accept', [ShiftAssignmentController::class, 'acceptShift']);
        Route::post('{shift}/reject', [ShiftAssignmentController::class, 'rejectShift']);
    });
});

// Advanced Chat Routes (keep separate for organization)
Route::middleware('auth:sanctum')->group(function () {
    
    // ==========================================
    // CHAT GROUPS & CHANNELS
    // ==========================================
    
    // List all groups/channels/DMs
    Route::get('/chat/groups', [ChatController::class, 'index']);
    
    // Create new group/channel
    Route::post('/chat/groups', [ChatController::class, 'store']);
    
    // Get group details
    Route::get('/chat/groups/{id}/details', [ChatController::class, 'show']);
    
    // Update group/channel
    Route::put('/chat/groups/{id}', [ChatController::class, 'update']);
    
    // Archive/Unarchive
    Route::post('/chat/groups/{id}/toggle-archive', [ChatController::class, 'toggleArchive']);
    
    // Toggle favorite
    Route::post('/chat/groups/{id}/toggle-favorite', [ChatController::class, 'toggleFavorite']);
    
    // Mark as read
    Route::post('/chat/groups/{id}/read', [ChatController::class, 'markAsRead']);
    
    // Toggle mute
    Route::post('/chat/groups/{id}/toggle-mute', [ChatController::class, 'toggleMute']);
    
    
    // ==========================================
    // CHANNEL SPECIFIC
    // ==========================================
    
    // Browse public channels
    Route::get('/chat/channels/browse', [ChatController::class, 'getBrowsableChannels']);
    
    // Join public channel
    Route::post('/chat/channels/{id}/join', [ChatController::class, 'joinChannel']);
    
    // Leave channel/group
    Route::post('/chat/groups/{id}/leave', [ChatController::class, 'leaveGroup']);
    
    
    // ==========================================
    // MEMBERS MANAGEMENT
    // ==========================================
    
    // Add members
    Route::post('/chat/groups/{id}/add-members', [ChatController::class, 'addMembers']);
    
    // Remove member
    Route::delete('/chat/groups/{groupId}/members/{userId}', [ChatController::class, 'removeMember']);
    
    // Update member role
    Route::put('/chat/groups/{groupId}/members/{userId}/role', [ChatController::class, 'updateMemberRole']);
    
    // Get available users
    Route::get('/chat/available-users', [ChatController::class, 'getAvailableUsers']);
    
    
    // ==========================================
    // DIRECT MESSAGES
    // ==========================================
    
    // Get or create direct message
    Route::post('/chat/direct-messages', [ChatController::class, 'getOrCreateDirectMessage']);
    
    
    // ==========================================
    // MESSAGES
    // ==========================================
    
    // Get messages in a group/channel
    Route::get('/chat/groups/{groupId}/messages', [ChatMessageController::class, 'index']);
    
    // Send message
    Route::post('/chat/groups/{groupId}/messages', [ChatMessageController::class, 'store']);
    
    // Edit message
    Route::put('/chat/groups/{groupId}/messages/{messageId}', [ChatMessageController::class, 'update']);
    
    // Delete message
    Route::delete('/chat/groups/{groupId}/messages/{messageId}', [ChatMessageController::class, 'destroy']);
    
    // Search messages
    Route::get('/chat/groups/{groupId}/messages/search', [ChatMessageController::class, 'search']);
    
    // Pin/Unpin message
    Route::post('/chat/groups/{groupId}/messages/{messageId}/toggle-pin', [ChatMessageController::class, 'togglePin']);
    
    // Get pinned messages
    Route::get('/chat/groups/{groupId}/pinned-messages', [ChatMessageController::class, 'getPinnedMessages']);
    
    
    // ==========================================
    // REACTIONS
    // ==========================================
    
    // Add/remove reaction
    Route::post('/chat/messages/{messageId}/reactions', [ChatReactionController::class, 'toggle']);
    
    // Get reactions for a message
    Route::get('/chat/messages/{messageId}/reactions', [ChatReactionController::class, 'index']);
    
    
    // ==========================================
    // THREADS
    // ==========================================
    
    // Get thread replies
    Route::get('/chat/threads/{threadId}/replies', [ChatThreadController::class, 'getReplies']);
    
    // Reply to thread
    Route::post('/chat/threads/{threadId}/reply', [ChatThreadController::class, 'reply']);
    
    // Create thread from message
    Route::post('/chat/messages/{messageId}/create-thread', [ChatThreadController::class, 'createFromMessage']);
    
    
    // ==========================================
    // BOOKMARKS
    // ==========================================
    
    // Bookmark message
    Route::post('/chat/messages/{messageId}/bookmark', [ChatMessageController::class, 'bookmark']);
    
    // Remove bookmark
    Route::delete('/chat/messages/{messageId}/bookmark', [ChatMessageController::class, 'removeBookmark']);
    
    // Get user's bookmarks
    Route::get('/chat/bookmarks', [ChatMessageController::class, 'getBookmarks']);
    
    
    // ==========================================
    // FILES
    // ==========================================
    
    // Get files in a group/channel
    Route::get('/chat/groups/{groupId}/files', [ChatMessageController::class, 'getFiles']);
    
    
    // ==========================================
    // MENTIONS
    // ==========================================
    
    // Get user's mentions
    Route::get('/chat/mentions', [ChatMessageController::class, 'getMentions']);
    
    // Mark mention as read
    Route::post('/chat/mentions/{mentionId}/read', [ChatMessageController::class, 'markMentionAsRead']);
    
    
    // ==========================================
    // TYPING INDICATORS
    // ==========================================
    
    // Update typing status
    Route::post('/chat/groups/{groupId}/typing', [ChatController::class, 'updateTypingStatus']);
    
    // Get who's typing
    Route::get('/chat/groups/{groupId}/typing', [ChatController::class, 'getTypingUsers']);
    
    
    // ==========================================
    // INVITATIONS
  //  --------------------------------------------------------------------------
    // Send invitation
    Route::post('/chat/groups/{groupId}/invite', [ChatController::class, 'inviteUser']);
    
    // Accept invitation
    Route::post('/chat/invitations/{invitationId}/accept', [ChatController::class, 'acceptInvitation']);
    
    // Decline invitation
    Route::post('/chat/invitations/{invitationId}/decline', [ChatController::class, 'declineInvitation']);
    
    // Get pending invitations
    Route::get('/chat/invitations', [ChatController::class, 'getPendingInvitations']);
});

Route::middleware('auth:sanctum')->prefix('chat/groups/{groupId}')->group(function () {
    // List all integrations for a channel
    Route::get('/integrations', [ChatIntegrationController::class, 'index']);
    
    // Create new integration
    Route::post('/integrations', [ChatIntegrationController::class, 'store']);
    
    // Update integration
    Route::patch('/integrations/{id}', [ChatIntegrationController::class, 'update']);
    
    // Delete integration
    Route::delete('/integrations/{id}', [ChatIntegrationController::class, 'destroy']);
    
    // Regenerate API key
    Route::post('/integrations/{id}/regenerate-key', [ChatIntegrationController::class, 'regenerateApiKey']);
    
    // Get integration logs
    Route::get('/integrations/{id}/logs', [ChatIntegrationController::class, 'logs']);
});

/*
|--------------------------------------------------------------------------
| Webhook Routes (Public - No Authentication)
|--------------------------------------------------------------------------
| These routes are used by external applications
*/

Route::prefix('webhooks/chat')->group(function () {
    // Send message to channel (requires API key in header)
    Route::post('/message', [ChatWebhookController::class, 'sendMessage']);
    
    // Get integration info (verify API key)
    Route::get('/info', [ChatWebhookController::class, 'getInfo']);
});

// Task Report Routes
// Option 2: Apply middleware to entire prefix
Route::prefix('tasks/reports')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/filters', [TaskReportController::class, 'getFilters']);
    Route::post('/generate', [TaskReportController::class, 'generateReport']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    // Activity tracking endpoints
    Route::post('/attendance/heartbeat', [ActivityTrackingController::class, 'heartbeat']);
    Route::get('/attendance/activity-status', [ActivityTrackingController::class, 'getStatus']);
    Route::post('/attendance/refresh-activity', [ActivityTrackingController::class, 'refreshActivity']);
});

// Admin/cron endpoint
Route::post('/attendance/check-idle-sessions', [ActivityTrackingController::class, 'checkIdleSessions'])
    ->middleware(['auth:sanctum', 'role:admin']);

    /*
|--------------------------------------------------------------------------
| Super Admin Routes
|--------------------------------------------------------------------------
| These routes are only accessible by users with is_superadmin = true
| They provide system-wide access to all businesses and advanced controls
*/

Route::middleware(['auth:sanctum', 'superadmin'])->prefix('superadmin')->group(function () {
    
    // Business Management
    Route::prefix('businesses')->group(function () {
        Route::get('/', [SuperAdminBusinessController::class, 'index']);
        Route::post('/', [SuperAdminBusinessController::class, 'store']);
        Route::get('/dashboard-stats', [SuperAdminBusinessController::class, 'getDashboardStats']);
        Route::get('/{business}', [SuperAdminBusinessController::class, 'show']);
        Route::put('/{business}', [SuperAdminBusinessController::class, 'update']);
        Route::delete('/{business}', [SuperAdminBusinessController::class, 'destroy']);
        
        // Business Actions
        Route::post('/{business}/suspend', [SuperAdminBusinessController::class, 'suspend']);
        Route::post('/{business}/activate', [SuperAdminBusinessController::class, 'activate']);
        Route::post('/{business}/update-limit', [SuperAdminBusinessController::class, 'updateEmployeeLimit']);
        
        // History & Logs
        Route::get('/{business}/activity-logs', [SuperAdminBusinessController::class, 'getActivityLogs']);
        Route::get('/{business}/limit-history', [SuperAdminBusinessController::class, 'getLimitHistory']);
    });
    
    // User Management
    Route::prefix('users')->group(function () {
        Route::get('/', [SuperAdminUserController::class, 'index']);
        Route::post('/', [SuperAdminUserController::class, 'store']);
        Route::get('/{user}', [SuperAdminUserController::class, 'show']);
        Route::put('/{user}', [SuperAdminUserController::class, 'update']);
        Route::post('/{user}/toggle-superadmin', [SuperAdminUserController::class, 'toggleSuperAdmin']);
        Route::post('/{user}/reset-password', [SuperAdminUserController::class, 'resetPassword']);
        Route::delete('/{user}', [SuperAdminUserController::class, 'destroy']);
    });
    
    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('/overview', [SuperAdminAnalyticsController::class, 'overview']);
        Route::get('/revenue', [SuperAdminAnalyticsController::class, 'revenue']);
        Route::get('/growth', [SuperAdminAnalyticsController::class, 'growth']);
        Route::get('/usage', [SuperAdminAnalyticsController::class, 'usage']);
    });
    
    // Settings
    Route::prefix('settings')->group(function () {
        Route::get('/subscription-tiers', [SuperAdminSettingsController::class, 'getSubscriptionTiers']);
        Route::put('/subscription-tiers', [SuperAdminSettingsController::class, 'updateSubscriptionTiers']);
        Route::get('/features', [SuperAdminSettingsController::class, 'getFeatures']);
        Route::put('/features', [SuperAdminSettingsController::class, 'updateFeatures']);
        Route::get('/system', [SuperAdminSettingsController::class, 'getSystemSettings']);
        Route::put('/system', [SuperAdminSettingsController::class, 'updateSystemSettings']);
    });
});

// Business Group Routes
Route::middleware(['auth:sanctum'])->group(function () {
    
    // ==================== BUSINESS GROUPS ====================
    Route::prefix('business-groups')->group(function () {
        Route::get('/', [BusinessGroupController::class, 'index']);
        Route::post('/', [BusinessGroupController::class, 'store']);
        Route::get('/{businessGroup}', [BusinessGroupController::class, 'show']);
        Route::put('/{businessGroup}', [BusinessGroupController::class, 'update']);
        Route::delete('/{businessGroup}', [BusinessGroupController::class, 'destroy']);
        
        // Group Members
        Route::get('/{businessGroup}/members', [BusinessGroupController::class, 'getMembers']);
        Route::post('/{businessGroup}/leave', [BusinessGroupController::class, 'leave']);
        
        // Group Settings
        Route::put('/{businessGroup}/settings', [BusinessGroupController::class, 'updateSettings']);
        
        // Group Statistics
        Route::get('/{businessGroup}/stats', [BusinessGroupController::class, 'getStats']);
        Route::get('/{businessGroup}/activity', [BusinessGroupController::class, 'getActivity']);
        
        // Group Resources
        Route::get('/{businessGroup}/employees', [BusinessGroupController::class, 'getGroupEmployees']);
        Route::get('/{businessGroup}/users', [BusinessGroupController::class, 'getGroupUsers']);
        Route::get('/{businessGroup}/businesses', [BusinessGroupController::class, 'getGroupBusinesses']);
    });

    // ==================== MEMBERSHIP MANAGEMENT ====================
    Route::prefix('business-group-memberships')->group(function () {
        Route::get('/', [BusinessGroupMembershipController::class, 'index']);
        Route::put('/{membership}', [BusinessGroupMembershipController::class, 'update']);
        Route::delete('/{membership}', [BusinessGroupMembershipController::class, 'destroy']);
        Route::post('/{membership}/suspend', [BusinessGroupMembershipController::class, 'suspend']);
        Route::post('/{membership}/activate', [BusinessGroupMembershipController::class, 'activate']);
    });

    // ==================== INVITATIONS ====================
    Route::prefix('business-group-invitations')->group(function () {
        Route::get('/', [BusinessGroupInvitationController::class, 'index']);
        Route::post('/', [BusinessGroupInvitationController::class, 'store']);
        Route::get('/{invitation}', [BusinessGroupInvitationController::class, 'show']);
        Route::post('/{invitation}/accept', [BusinessGroupInvitationController::class, 'accept']);
        Route::post('/{invitation}/reject', [BusinessGroupInvitationController::class, 'reject']);
        Route::delete('/{invitation}', [BusinessGroupInvitationController::class, 'cancel']);
    });

    // ==================== CROSS-BUSINESS TICKETS ====================
    Route::prefix('group-tickets')->group(function () {
        Route::get('/', [GroupTicketController::class, 'index']);
        Route::post('/', [GroupTicketController::class, 'store']);
        Route::get('/{ticket}', [GroupTicketController::class, 'show']);
        Route::put('/{ticket}', [GroupTicketController::class, 'update']);
        Route::post('/{ticket}/assign-to-business', [GroupTicketController::class, 'assignToBusiness']);
        Route::get('/{ticket}/comments', [GroupTicketController::class, 'getComments']);
        Route::post('/{ticket}/comments', [GroupTicketController::class, 'addComment']);
    });

    // ==================== CROSS-BUSINESS TASKS ====================
    Route::prefix('group-tasks')->group(function () {
        Route::get('/', [GroupTaskController::class, 'index']);
        Route::post('/', [GroupTaskController::class, 'store']);
        Route::get('/{task}', [GroupTaskController::class, 'show']);
        Route::put('/{task}', [GroupTaskController::class, 'update']);
        Route::post('/{task}/assign-to-business', [GroupTaskController::class, 'assignToBusiness']);
    });
});