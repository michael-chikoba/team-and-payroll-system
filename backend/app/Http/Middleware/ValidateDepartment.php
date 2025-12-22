<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class ValidateDepartment
{
    /**
     * Handle an incoming request.
     * Validates that the employee's department is valid for their business/country
     * 
     * IMPORTANT: This middleware ONLY applies to employee and manager roles
     * Admin users bypass all department validation
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // ========================================
        // ADMIN BYPASS - No department validation
        // ========================================
        if ($user && $user->role === 'admin') {
            Log::info('Department validation bypassed for admin user', [
                'user_id' => $user->id,
                'role' => $user->role
            ]);
            
            return $next($request);
        }

        // ========================================
        // EMPLOYEE & MANAGER - Apply department validation
        // ========================================
        
        // Only validate for employee and manager roles
        if ($user->role !== 'employee' && $user->role !== 'manager') {
            Log::info('Department validation not applicable for role', [
                'user_id' => $user->id,
                'role' => $user->role
            ]);
            
            return $next($request);
        }

        // Get employee record
        $employee = $user->employee ?? null;

        if (!$employee) {
            Log::warning('User has no employee record', [
                'user_id' => $user->id,
                'role' => $user->role
            ]);
            
            return response()->json([
                'message' => 'No employee record found',
                'error' => 'no_employee_record'
            ], 403);
        }

        // Check if employee has a department
        if (!$employee->department) {
            Log::warning('Employee has no department assigned', [
                'user_id' => $user->id,
                'employee_id' => $employee->id,
                'role' => $user->role
            ]);
            
            return response()->json([
                'message' => 'No department assigned. Please contact HR.',
                'error' => 'no_department',
                'action' => 'contact_hr'
            ], 403);
        }

        // Get valid departments for this business/country
        $businessId = $employee->business_id;
        $countryCode = $employee->country->code ?? null;
        
        $validDepartments = SystemSetting::getValidDepartments($businessId, $countryCode);

        // Validate department
        if (!in_array($employee->department, $validDepartments)) {
            Log::warning('Employee has invalid department', [
                'user_id' => $user->id,
                'employee_id' => $employee->id,
                'role' => $user->role,
                'department' => $employee->department,
                'valid_departments' => $validDepartments
            ]);

            return response()->json([
                'message' => 'Invalid department. Please contact HR to update your department.',
                'error' => 'invalid_department',
                'current_department' => $employee->department,
                'valid_departments' => $validDepartments,
                'action' => 'contact_hr'
            ], 403);
        }

        // Add department info to request for use in controllers
        $request->merge([
            'employee_department' => $employee->department,
            'valid_departments' => $validDepartments,
            'department_validated' => true
        ]);

        Log::info('Department validation passed', [
            'user_id' => $user->id,
            'role' => $user->role,
            'department' => $employee->department
        ]);

        return $next($request);
    }
}