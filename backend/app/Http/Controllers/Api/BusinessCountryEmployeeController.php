<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\Manager;
use App\Models\Document;
use App\Models\User;
use App\Models\Business;
use App\Models\Country;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BusinessCountryEmployeeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        try {
            $startTime = microtime(true);
            
            $query = Employee::with(['user', 'manager', 'business', 'country']);
            
            // Filter by business if provided
            if ($request->has('business_id')) {
                $query->where('business_id', $request->business_id);
            }
            
            // Filter by country if provided
            if ($request->has('country_id')) {
                $query->where('country_id', $request->country_id);
            }
            
            $employees = $query->get();
            
            Log::info('Raw employees data', [
                'sample' => $employees->first() ? $employees->first()->toArray() : 'no data'
            ]);
            
            $resourceCollection = EmployeeResource::collection($employees);
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Employees fetched successfully', [
                'count' => $employees->count(),
                'execution_time_ms' => $executionTime,
                'business_id' => $request->business_id ?? 'all',
                'country_id' => $request->country_id ?? 'all',
            ]);
            
            return $resourceCollection;
        } catch (\Exception $e) {
            Log::error('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Failed to fetch employees', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get all available businesses
     */
    public function businesses(): JsonResponse
    {
        try {
            $businesses = Business::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'country_id']);
            
            Log::info('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Businesses fetched', [
                'count' => $businesses->count(),
            ]);
            
            return response()->json([
                'data' => $businesses
            ]);
        } catch (\Exception $e) {
            Log::error('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Failed to fetch businesses', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Failed to fetch businesses'
            ], 500);
        }
    }

    /**
     * Get all available countries
     */
    public function countries(): JsonResponse
    {
        try {
            $countries = Country::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'currency_code', 'currency_symbol', 'phone_code']);
            
            Log::info('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Countries fetched', [
                'count' => $countries->count(),
            ]);
            
            return response()->json([
                'data' => $countries
            ]);
        } catch (\Exception $e) {
            Log::error('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Failed to fetch countries', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Failed to fetch countries'
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'role' => 'required|in:employee,manager,admin',
                'position' => 'required|string|max:255',
                'department' => 'required|string|max:255',
                'base_salary' => 'required|numeric|min:0',
                'transport_allowance' => 'required|numeric|min:0',
                'lunch_allowance' => 'required|numeric|min:0',
                'hire_date' => 'required|date',
                'employment_type' => 'required|in:full_time,part_time,contract',
                'manager_id' => 'nullable|exists:users,id',
                'business_id' => 'required|exists:businesses,id',
                'country_id' => 'required|exists:countries,id',
            ]);
            
            Log::info('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Employee validation passed', [
                'email' => $validated['email'],
                'role' => $validated['role'],
                'business_id' => $validated['business_id'],
                'country_id' => $validated['country_id'],
            ]);
            
            DB::beginTransaction();
            
            // Create user
            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make('Password123!'),
                'role' => $validated['role'],
            ]);
            
            Log::info('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: User created', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
            ]);
            
            // Generate unique employee ID with business and country prefix
            $business = Business::find($validated['business_id']);
            $country = Country::find($validated['country_id']);
            
            $lastEmployee = Employee::where('business_id', $validated['business_id'])
                ->where('country_id', $validated['country_id'])
                ->latest('id')
                ->first();
            
            $nextNumber = $lastEmployee ? (intval(substr($lastEmployee->employee_id, -4)) + 1) : 1;
            $employeeId = strtoupper($country->code) . '-' . strtoupper($business->code) . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            
            // Create employee record for all roles
            $employee = Employee::create([
                'user_id' => $user->id,
                'manager_id' => $validated['role'] !== 'manager' && $validated['role'] !== 'admin'
                    ? $validated['manager_id']
                    : null,
                'business_id' => $validated['business_id'],
                'country_id' => $validated['country_id'],
                'employee_id' => $employeeId,
                'position' => $validated['position'],
                'department' => $validated['department'],
                'base_salary' => $validated['base_salary'],
                'transport_allowance' => $validated['transport_allowance'],
                'lunch_allowance' => $validated['lunch_allowance'],
                'hire_date' => $validated['hire_date'],
                'employment_type' => $validated['employment_type'],
            ]);
            
            // Handle manager record creation
            if ($validated['role'] === 'manager' || $validated['role'] === 'admin') {
                $existingManager = Manager::where('user_id', $user->id)->first();
                
                if (!$existingManager) {
                    Manager::create([
                        'user_id' => $user->id,
                        'department' => $validated['department'],
                        'business_id' => $validated['business_id'],
                        'country_id' => $validated['country_id'],
                        'max_team_size' => 10,
                        'permissions' => json_encode([]),
                    ]);
                    
                    Log::info('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Manager record created', [
                        'user_id' => $user->id,
                        'department' => $validated['department'],
                        'business_id' => $validated['business_id'],
                        'country_id' => $validated['country_id'],
                    ]);
                } else {
                    Log::info('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Manager record already exists', [
                        'user_id' => $user->id
                    ]);
                }
            }
            
            DB::commit();
            
            Log::info('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Employee created successfully', [
                'employee_id' => $employee->id,
                'employee_number' => $employeeId,
                'role' => $validated['role'],
                'business_id' => $validated['business_id'],
                'country_id' => $validated['country_id'],
            ]);
            
            $employee->load(['user', 'manager', 'business', 'country']);
            
            return response()->json([
                'employee' => new EmployeeResource($employee),
                'message' => 'Employee created successfully. Default password is: Password123!'
            ], 201);
            
        } catch (ValidationException $e) {
            DB::rollBack();
            Log::warning('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Employee validation failed', [
                'errors' => $e->errors(),
            ]);
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::error('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Database error during employee creation', [
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'sql_state' => $e->errorInfo[0] ?? 'unknown',
            ]);
            return response()->json([
                'message' => 'Database error occurred. Please check if the data is valid.',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Unexpected error during employee creation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'An unexpected error occurred while creating employee. Please try again.'
            ], 500);
        }
    }

    public function show(Employee $employee): EmployeeResource
    {
        try {
            $employee->load(['user', 'manager', 'business', 'country', 'attendances', 'leaves', 'payslips']);
            return new EmployeeResource($employee);
        } catch (\Exception $e) {
            Log::error('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Failed to fetch employee details', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function update(Request $request, Employee $employee): JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $employee->user_id,
                'role' => 'sometimes|in:employee,manager,admin',
                'position' => 'sometimes|string|max:255',
                'department' => 'sometimes|string|max:255',
                'base_salary' => 'sometimes|numeric|min:0',
                'transport_allowance' => 'sometimes|numeric|min:0',
                'lunch_allowance' => 'sometimes|numeric|min:0',
                'employment_type' => 'sometimes|in:full_time,part_time,contract',
                'manager_id' => 'nullable|exists:users,id',
                'business_id' => 'sometimes|exists:businesses,id',
                'country_id' => 'sometimes|exists:countries,id',
            ]);
            
            Log::info('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Updating employee', [
                'employee_id' => $employee->id,
                'current_role' => $employee->user->role,
                'new_role' => $validated['role'] ?? null,
            ]);
            
            DB::beginTransaction();
            
            $userUpdates = [];
            $employeeUpdates = [];
            
            // Store old role for comparison
            $oldRole = $employee->user->role;
            $newRole = $validated['role'] ?? $oldRole;
            
            // Update user details if provided
            if (isset($validated['first_name'])) $userUpdates['first_name'] = $validated['first_name'];
            if (isset($validated['last_name'])) $userUpdates['last_name'] = $validated['last_name'];
            if (isset($validated['email'])) $userUpdates['email'] = $validated['email'];
            if (isset($validated['role'])) $userUpdates['role'] = $validated['role'];
            
            if (!empty($userUpdates)) {
                $employee->user->update($userUpdates);
            }
            
            // Handle role changes for manager records
            if ($oldRole !== $newRole) {
                Log::info('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Role change detected', [
                    'old_role' => $oldRole,
                    'new_role' => $newRole,
                    'user_id' => $employee->user_id
                ]);
                
                // Changing TO manager or admin
                if (($newRole === 'manager' || $newRole === 'admin') &&
                    ($oldRole !== 'manager' && $oldRole !== 'admin')) {
                    
                    $existingManager = Manager::where('user_id', $employee->user_id)->first();
                    
                    if (!$existingManager) {
                        Manager::create([
                            'user_id' => $employee->user_id,
                            'department' => $validated['department'] ?? $employee->department,
                            'business_id' => $validated['business_id'] ?? $employee->business_id,
                            'country_id' => $validated['country_id'] ?? $employee->country_id,
                            'max_team_size' => 10,
                            'permissions' => json_encode([]),
                        ]);
                        Log::info('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Manager record created on role change');
                    }
                    
                    // Clear manager_id when becoming a manager/admin
                    $employeeUpdates['manager_id'] = null;
                }
                // Changing FROM manager or admin to employee
                elseif ($newRole === 'employee' &&
                        ($oldRole === 'manager' || $oldRole === 'admin')) {
                    
                    Manager::where('user_id', $employee->user_id)->delete();
                    Log::info('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Manager record deleted on role change');
                    
                    // Employee should have a manager assigned
                    if (isset($validated['manager_id'])) {
                        $employeeUpdates['manager_id'] = $validated['manager_id'];
                    }
                }
            }
            
            // Update employee details
            if (isset($validated['position'])) $employeeUpdates['position'] = $validated['position'];
            if (isset($validated['department'])) {
                $employeeUpdates['department'] = $validated['department'];
                
                // Update manager's department if user is a manager/admin
                if ($newRole === 'manager' || $newRole === 'admin') {
                    Manager::where('user_id', $employee->user_id)
                        ->update(['department' => $validated['department']]);
                }
            }
            if (isset($validated['base_salary'])) $employeeUpdates['base_salary'] = $validated['base_salary'];
            if (isset($validated['transport_allowance'])) $employeeUpdates['transport_allowance'] = $validated['transport_allowance'];
            if (isset($validated['lunch_allowance'])) $employeeUpdates['lunch_allowance'] = $validated['lunch_allowance'];
            if (isset($validated['employment_type'])) $employeeUpdates['employment_type'] = $validated['employment_type'];
            if (isset($validated['business_id'])) $employeeUpdates['business_id'] = $validated['business_id'];
            if (isset($validated['country_id'])) $employeeUpdates['country_id'] = $validated['country_id'];
            
            // Handle manager_id updates for employees only
            if ($newRole === 'employee' && isset($validated['manager_id'])) {
                $employeeUpdates['manager_id'] = $validated['manager_id'];
            } elseif ($newRole !== 'employee') {
                $employeeUpdates['manager_id'] = null;
            }
            
            if (!empty($employeeUpdates)) {
                $employee->update($employeeUpdates);
            }
            
            DB::commit();
            
            Log::info('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Employee updated successfully', [
                'employee_id' => $employee->id,
                'role_changed' => $oldRole !== $newRole,
            ]);
            
            $employee->load(['user', 'manager', 'business', 'country']);
            
            return response()->json([
                'employee' => new EmployeeResource($employee),
                'message' => 'Employee updated successfully'
            ]);
            
        } catch (ValidationException $e) {
            DB::rollBack();
            Log::warning('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Employee update validation failed', [
                'employee_id' => $employee->id,
                'errors' => $e->errors(),
            ]);
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::error('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Database error during employee update', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Database error occurred during update. Please try again.',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Failed to update employee', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Failed to update employee. Please try again.'
            ], 500);
        }
    }

    public function destroy(Employee $employee): JsonResponse
    {
        Log::info('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Deleting employee', [
            'employee_id' => $employee->id,
            'user_id' => $employee->user_id,
            'business_id' => $employee->business_id,
            'country_id' => $employee->country_id,
        ]);
        
        try {
            DB::beginTransaction();
            
            $user = $employee->user;
            
            // Delete manager record if exists
            if ($user->role === 'manager' || $user->role === 'admin') {
                Manager::where('user_id', $user->id)->delete();
                Log::info('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Manager record deleted');
            }
            
            // Delete employee record
            $employee->delete();
            
            // Delete user
            if ($user) {
                $user->delete();
            }
            
            DB::commit();
            
            Log::info('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Employee deleted successfully');
            
            return response()->json([
                'message' => 'Employee deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Failed to delete employee', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Failed to delete employee. Please try again.'
            ], 500);
        }
    }

    public function managers(Request $request): JsonResponse
    {
        try {
            $query = User::whereIn('role', ['manager', 'admin'])
                ->with(['employee.business', 'employee.country']);
            
            // Filter by business if provided
            if ($request->has('business_id')) {
                $query->whereHas('employee', function($q) use ($request) {
                    $q->where('business_id', $request->business_id);
                });
            }
            
            // Filter by country if provided
            if ($request->has('country_id')) {
                $query->whereHas('employee', function($q) use ($request) {
                    $q->where('country_id', $request->country_id);
                });
            }
            
            $managers = $query->get()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'department' => $user->employee->department ?? 'General',
                    'position' => $user->employee->position ?? 'Manager',
                    'business' => $user->employee->business->name ?? null,
                    'country' => $user->employee->country->name ?? null,
                ];
            });
            
            Log::info('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Managers fetched', [
                'count' => $managers->count(),
                'business_id' => $request->business_id ?? 'all',
                'country_id' => $request->country_id ?? 'all',
            ]);
            
            return response()->json($managers);
        } catch (\Exception $e) {
            Log::error('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Failed to fetch managers', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Failed to fetch managers list'
            ], 500);
        }
    }

    /**
     * Search employees by name or email
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $searchTerm = $request->query('q');
        
        try {
            $query = Employee::with(['user', 'manager', 'business', 'country'])
                ->whereHas('user', function($q) use ($searchTerm) {
                    $q->where('first_name', 'like', "%{$searchTerm}%")
                      ->orWhere('last_name', 'like', "%{$searchTerm}%")
                      ->orWhere('email', 'like', "%{$searchTerm}%");
                });
            
            // Filter by business if provided
            if ($request->has('business_id')) {
                $query->where('business_id', $request->business_id);
            }
            
            // Filter by country if provided
            if ($request->has('country_id')) {
                $query->where('country_id', $request->country_id);
            }
            
            $employees = $query->get();
            
            return EmployeeResource::collection($employees);
        } catch (\Exception $e) {
            Log::error('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Employee search failed', [
                'search_term' => $searchTerm,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get all available departments from system settings
     */
    public function departments(): JsonResponse
    {
        try {
            $departmentsSetting = SystemSetting::where('key', 'departments')->first();
            
            if (!$departmentsSetting) {
                return response()->json([
                    'data' => []
                ]);
            }

            $departments = json_decode($departmentsSetting->value, true) ?? [];
            
            // Extract department names from the JSON structure
            $departmentList = array_map(function($dept) {
                return $dept['name'];
            }, $departments);

            Log::info('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Departments fetched', [
                'count' => count($departmentList),
            ]);

            return response()->json([
                'data' => $departmentList
            ]);
        } catch (\Exception $e) {
            Log::error('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Failed to fetch departments', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Failed to fetch departments'
            ], 500);
        }
    }

    public function teamEmployees(Request $request): AnonymousResourceCollection
    {
        $managerId = $request->query('manager_id');
        if (!$managerId) abort(400, 'Manager ID required');
        
        $query = Employee::where('manager_id', $managerId)
                         ->with(['user', 'business', 'country']);
        
        // Filter by business if provided
        if ($request->has('business_id')) {
            $query->where('business_id', $request->business_id);
        }
        
        // Filter by country if provided
        if ($request->has('country_id')) {
            $query->where('country_id', $request->country_id);
        }
        
        $employees = $query->get();
        
        return EmployeeResource::collection($employees);
    }

    /**
     * Health check for business country employee controller
     */
    public function health(): JsonResponse
    {
        Log::info('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Health check requested');
        
        try {
            $userCount = User::count();
            $employeeCount = Employee::count();
            $businessCount = Business::count();
            $countryCount = Country::count();
            $databaseStatus = 'connected';
            
            // Test database connection
            DB::select('SELECT 1');
            
            Log::info('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Health check passed', [
                'user_count' => $userCount,
                'employee_count' => $employeeCount,
                'business_count' => $businessCount,
                'country_count' => $countryCount,
                'database_status' => $databaseStatus
            ]);
            
            return response()->json([
                'status' => 'healthy',
                'user_count' => $userCount,
                'employee_count' => $employeeCount,
                'business_count' => $businessCount,
                'country_count' => $countryCount,
                'database_status' => $databaseStatus,
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('BUSINESS_COUNTRY_EMPLOYEE_CONTROLLER: Health check failed', [
                'error' => $e->getMessage(),
                'database_connection' => config('database.default')
            ]);
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }
}