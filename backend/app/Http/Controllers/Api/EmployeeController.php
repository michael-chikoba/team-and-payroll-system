<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\Manager;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
      public function index(): AnonymousResourceCollection
    {
        try {
            $startTime = microtime(true);
            
            $employees = Employee::with(['user', 'manager'])->get();
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('EMPLOYEE_CONTROLLER: Employees fetched successfully', [
                'count' => $employees->count(),
                'execution_time_ms' => $executionTime,
            ]);
            
            return EmployeeResource::collection($employees);

        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to fetch employees', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
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
                'hire_date' => 'required|date',
                'employment_type' => 'required|in:full_time,part_time,contract',
                'manager_id' => 'nullable|exists:users,id',
            ]);

            Log::info('EMPLOYEE_CONTROLLER: Employee validation passed', [
                'email' => $validated['email'],
                'role' => $validated['role'],
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

            Log::info('EMPLOYEE_CONTROLLER: User created', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
            ]);

            // Generate unique employee ID
            $lastEmployee = Employee::latest('id')->first();
            $nextNumber = $lastEmployee ? (intval(substr($lastEmployee->employee_id, 3)) + 1) : 1;
            $employeeId = 'EMP' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // Create employee record for all roles
            $employee = Employee::create([
                'user_id' => $user->id,
                'manager_id' => $validated['role'] !== 'manager' && $validated['role'] !== 'admin' 
                    ? $validated['manager_id'] 
                    : null,
                'employee_id' => $employeeId,
                'position' => $validated['position'],
                'department' => $validated['department'],
                'base_salary' => $validated['base_salary'],
                'hire_date' => $validated['hire_date'],
                'employment_type' => $validated['employment_type'],
            ]);

            // Handle manager record creation
            if ($validated['role'] === 'manager' || $validated['role'] === 'admin') {
                // Check if manager record already exists for this user
                $existingManager = Manager::where('user_id', $user->id)->first();
                
                if (!$existingManager) {
                    Manager::create([
                        'user_id' => $user->id,
                        'department' => $validated['department'],
                        'max_team_size' => 10,
                        'permissions' => json_encode([]),
                    ]);

                    Log::info('EMPLOYEE_CONTROLLER: Manager record created', [
                        'user_id' => $user->id,
                        'department' => $validated['department']
                    ]);
                } else {
                    Log::info('EMPLOYEE_CONTROLLER: Manager record already exists', [
                        'user_id' => $user->id
                    ]);
                }
            }

            DB::commit();

            Log::info('EMPLOYEE_CONTROLLER: Employee created successfully', [
                'employee_id' => $employee->id,
                'employee_number' => $employeeId,
                'role' => $validated['role'],
            ]);

            $employee->load(['user', 'manager']);

            return response()->json([
                'employee' => new EmployeeResource($employee),
                'message' => 'Employee created successfully. Default password is: Password123!'
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            
            Log::warning('EMPLOYEE_CONTROLLER: Employee validation failed', [
                'errors' => $e->errors(),
            ]);

            throw $e;

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            
            Log::error('EMPLOYEE_CONTROLLER: Database error during employee creation', [
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'sql_state' => $e->errorInfo[0] ?? 'unknown',
            ]);

            return response()->json([
                'message' => 'Database error occurred. This might be due to a duplicate entry. Please check if the manager for this department already exists.',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('EMPLOYEE_CONTROLLER: Unexpected error during employee creation', [
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
            $employee->load(['user', 'manager', 'attendances', 'leaves', 'payslips']);
            
            return new EmployeeResource($employee);

        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to fetch employee details', [
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
                'employment_type' => 'sometimes|in:full_time,part_time,contract',
                'manager_id' => 'nullable|exists:users,id',
            ]);

            Log::info('EMPLOYEE_CONTROLLER: Updating employee', [
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
                Log::info('EMPLOYEE_CONTROLLER: Role change detected', [
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
                            'max_team_size' => 10,
                            'permissions' => json_encode([]),
                        ]);
                        Log::info('EMPLOYEE_CONTROLLER: Manager record created on role change');
                    }
                    
                    // Clear manager_id when becoming a manager/admin
                    $employeeUpdates['manager_id'] = null;
                }
                // Changing FROM manager or admin to employee
                elseif ($newRole === 'employee' && 
                        ($oldRole === 'manager' || $oldRole === 'admin')) {
                    
                    Manager::where('user_id', $employee->user_id)->delete();
                    Log::info('EMPLOYEE_CONTROLLER: Manager record deleted on role change');
                    
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
            if (isset($validated['employment_type'])) $employeeUpdates['employment_type'] = $validated['employment_type'];
            
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

            Log::info('EMPLOYEE_CONTROLLER: Employee updated successfully', [
                'employee_id' => $employee->id,
                'role_changed' => $oldRole !== $newRole,
            ]);

            $employee->load(['user', 'manager']);

            return response()->json([
                'employee' => new EmployeeResource($employee),
                'message' => 'Employee updated successfully'
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            
            Log::warning('EMPLOYEE_CONTROLLER: Employee update validation failed', [
                'employee_id' => $employee->id,
                'errors' => $e->errors(),
            ]);

            throw $e;

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            
            Log::error('EMPLOYEE_CONTROLLER: Database error during employee update', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Database error occurred during update. Please try again.',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('EMPLOYEE_CONTROLLER: Failed to update employee', [
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
        Log::info('EMPLOYEE_CONTROLLER: Deleting employee', [
            'employee_id' => $employee->id,
            'user_id' => $employee->user_id,
        ]);

        try {
            DB::beginTransaction();

            $user = $employee->user;
            
            // Delete manager record if exists
            if ($user->role === 'manager' || $user->role === 'admin') {
                Manager::where('user_id', $user->id)->delete();
                Log::info('EMPLOYEE_CONTROLLER: Manager record deleted');
            }
            
            // Delete employee record
            $employee->delete();
            
            // Delete user
            if ($user) {
                $user->delete();
            }

            DB::commit();

            Log::info('EMPLOYEE_CONTROLLER: Employee deleted successfully');
            
            return response()->json([
                'message' => 'Employee deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('EMPLOYEE_CONTROLLER: Failed to delete employee', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to delete employee. Please try again.'
            ], 500);
        }
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        
        try {
            $employee = $user->employee;
            
            return response()->json([
                'user' => $user,
                'employee' => $employee ? new EmployeeResource($employee->load(['manager'])) : null,
            ]);

        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to fetch employee profile', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        
        try {
            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
            ]);

            $user->update($validated);

            return response()->json([
                'user' => $user,
                'message' => 'Profile updated successfully'
            ]);

        } catch (ValidationException $e) {
            throw $e;

        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to update employee profile', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to update profile. Please try again.'
            ], 500);
        }
    }
     public function managers(): JsonResponse
    {
        try {
            $managers = User::whereIn('role', ['manager', 'admin'])
                ->with(['employee'])
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'department' => $user->employee->department ?? 'General',
                        'position' => $user->employee->position ?? 'Manager'
                    ];
                });
        
            Log::info('EMPLOYEE_CONTROLLER: Managers fetched', [
                'count' => $managers->count(),
            ]);
        
            return response()->json($managers);

        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to fetch managers', [
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
            $employees = Employee::with(['user', 'manager'])
                ->whereHas('user', function($query) use ($searchTerm) {
                    $query->where('first_name', 'like', "%{$searchTerm}%")
                          ->orWhere('last_name', 'like', "%{$searchTerm}%")
                          ->orWhere('email', 'like', "%{$searchTerm}%");
                })
                ->get();

            return EmployeeResource::collection($employees);

        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Employee search failed', [
                'search_term' => $searchTerm,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Health check for employee controller
     */
    public function health(): JsonResponse
    {
        Log::info('EMPLOYEE_CONTROLLER: Health check requested');
        
        try {
            $userCount = User::count();
            $employeeCount = Employee::count();
            $databaseStatus = 'connected';
            
            // Test database connection
            DB::select('SELECT 1');
            
            Log::info('EMPLOYEE_CONTROLLER: Health check passed', [
                'user_count' => $userCount,
                'employee_count' => $employeeCount,
                'database_status' => $databaseStatus
            ]);

            return response()->json([
                'status' => 'healthy',
                'user_count' => $userCount,
                'employee_count' => $employeeCount,
                'database_status' => $databaseStatus,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Health check failed', [
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


    public function initializeLeaveBalances(Request $request)
{
    $employee = $request->user()->employee;
    
    if (!$employee) {
        return response()->json(['message' => 'Employee profile not found'], 404);
    }

    try {
        LeaveBalance::initializeForEmployee($employee);
        
        return response()->json([
            'message' => 'Leave balances initialized successfully',
            'balances' => LeaveBalance::where('employee_id', $employee->id)->get()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to initialize leave balances',
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function teamEmployees(Request $request): AnonymousResourceCollection 
    {
        $managerId = $request->query('manager_id');
        if (!$managerId) abort(400, 'Manager ID required');
        
        $employees = Employee::where('manager_id', $managerId)
                           ->with(['user'])
                           ->get();
        return EmployeeResource::collection($employees);
    }
}