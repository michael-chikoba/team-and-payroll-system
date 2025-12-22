<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    /**
     * Get valid departments for the authenticated user's business/country
     * 
     * IMPORTANT: Admin users receive special response indicating they don't need departments
     */
    public function getValidDepartments(Request $request)
    {
        $user = $request->user();

        // ========================================
        // ADMIN BYPASS - Admins don't need departments
        // ========================================
        if ($user->role === 'admin') {
            Log::info('Admin user requesting departments - returning bypass response', [
                'user_id' => $user->id,
                'role' => $user->role
            ]);

            return response()->json([
                'is_admin' => true,
                'message' => 'Admin users have unrestricted access and do not require department assignment',
                'departments' => [],
                'current_department' => null
            ]);
        }

        // ========================================
        // EMPLOYEE & MANAGER - Return departments
        // ========================================
        
        $employee = $user->employee;

        if (!$employee) {
            Log::warning('Non-admin user has no employee record', [
                'user_id' => $user->id,
                'role' => $user->role
            ]);

            return response()->json([
                'departments' => [],
                'current_department' => null,
                'message' => 'No employee record found',
                'error' => 'no_employee_record'
            ], 404);
        }

        $businessId = $employee->business_id;
        $countryCode = $employee->country->code ?? null;

        Log::info('Fetching departments for employee/manager', [
            'user_id' => $user->id,
            'role' => $user->role,
            'business_id' => $businessId,
            'country_code' => $countryCode,
            'current_department' => $employee->department
        ]);

        // Get departments from system settings
        $departments = SystemSetting::getValidDepartments($businessId, $countryCode);

        return response()->json([
            'is_admin' => false,
            'departments' => $departments,
            'current_department' => $employee->department,
            'business_id' => $businessId,
            'country_code' => $countryCode,
            'business_name' => $employee->business->name ?? null,
            'country_name' => $employee->country->name ?? null
        ]);
    }

    /**
     * Get all system settings for the authenticated user's context
     */
    public function getSystemSettings(Request $request)
    {
        $user = $request->user();

        // Admin gets global settings
        if ($user->role === 'admin') {
            Log::info('Admin user requesting system settings', [
                'user_id' => $user->id
            ]);

            $settings = SystemSetting::getAllSettings(null, 'global');

            return response()->json([
                'is_admin' => true,
                'settings' => $settings,
                'scope' => 'global'
            ]);
        }

        // Employee/Manager gets their business/country settings
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'settings' => [],
                'message' => 'No employee record found'
            ], 404);
        }

        $businessId = $employee->business_id;
        $countryCode = $employee->country->code ?? null;

        // Get all settings for this business/country context
        $settings = SystemSetting::getAllSettings($businessId, $countryCode);

        return response()->json([
            'is_admin' => false,
            'settings' => $settings,
            'business_id' => $businessId,
            'country_code' => $countryCode,
            'scope' => 'business_country'
        ]);
    }

    /**
     * Check if a department is valid for the user's business/country
     * Admin users always get valid response
     */
    public function validateDepartment(Request $request)
    {
        $request->validate([
            'department' => 'required|string'
        ]);

        $user = $request->user();
        $department = $request->department;

        // ========================================
        // ADMIN BYPASS
        // ========================================
        if ($user->role === 'admin') {
            Log::info('Admin validating department - auto-approved', [
                'user_id' => $user->id,
                'department' => $department
            ]);

            return response()->json([
                'valid' => true,
                'is_admin' => true,
                'department' => $department,
                'message' => 'Admin users can access all departments'
            ]);
        }

        // ========================================
        // EMPLOYEE & MANAGER
        // ========================================
        
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'valid' => false,
                'message' => 'No employee record found'
            ], 404);
        }

        $businessId = $employee->business_id;
        $countryCode = $employee->country->code ?? null;

        // Get valid departments
        $validDepartments = SystemSetting::getValidDepartments($businessId, $countryCode);

        $isValid = in_array($department, $validDepartments);

        Log::info('Department validation result', [
            'user_id' => $user->id,
            'role' => $user->role,
            'department' => $department,
            'valid' => $isValid
        ]);

        return response()->json([
            'valid' => $isValid,
            'is_admin' => false,
            'department' => $department,
            'valid_departments' => $validDepartments,
            'current_department' => $employee->department,
            'message' => $isValid 
                ? 'Department is valid' 
                : 'Department is not valid for this business/country'
        ]);
    }
}