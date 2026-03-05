<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\Manager;
use App\Models\Document;
use App\Models\User;
use App\Models\SystemSetting;
use App\Models\Country; 
use App\Models\LeaveBalance; 
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\EncryptionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{

protected EncryptionService $encryption;

    public function __construct(EncryptionService $encryption)
    {
        $this->encryption = $encryption;
    }
 
    /**
     * Display a listing of employees with encrypted fields handled
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $startTime = microtime(true);
            
            // Get employees with all necessary relationships
            $employees = $this->getBusinessScopedEmployees($request)
                ->with(['user', 'manager', 'business', 'country'])
                ->get();
            
            Log::info('EMPLOYEE_CONTROLLER: Raw employees data', [
                'count' => $employees->count(),
                'business_filter' => $request->query('business_id'),
            ]);
            
            // Transform the data - encrypted fields will auto-decrypt via trait
            $transformedEmployees = $employees->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'employee_id' => $employee->employee_id,
                    'user_id' => $employee->user_id,
                    'business_id' => $employee->business_id,
                    'country_id' => $employee->country_id,
                    'manager_id' => $employee->manager_id,
                    'first_name' => $employee->user->first_name ?? $employee->first_name,
                    'last_name' => $employee->user->last_name ?? $employee->last_name,
                    'email' => $employee->user->email ?? $employee->email,
                    'role' => $employee->user->role ?? 'employee',
                    'position' => $employee->position,
                    'department' => $employee->department,
                    'base_salary' => (float) $employee->base_salary,
                    'transport_allowance' => (float) $employee->transport_allowance,
                    'lunch_allowance' => (float) $employee->lunch_allowance,
                    'employment_type' => $employee->employment_type,
                    'hire_date' => $employee->hire_date,
                    'created_at' => $employee->created_at,
                    'updated_at' => $employee->updated_at,
                    
                    // Encrypted fields - will auto-decrypt based on user permissions
                    'phone' => $employee->phone, // Auto-decrypts via trait
                    'national_id' => $employee->national_id, // Auto-decrypts
                    'address' => $employee->address, // Auto-decrypts
                    'emergency_contact' => $employee->emergency_contact, // Auto-decrypts
                    'bank_details' => $employee->bank_details, // Auto-decrypts JSON
                    
                    // Business relationship
                    'business' => $employee->business ? [
                        'id' => $employee->business->id,
                        'name' => $employee->business->name,
                        'industry' => $employee->business->industry,
                    ] : null,
                    
                    // Country relationship
                    'country' => $employee->country ? [
                        'id' => $employee->country->id,
                        'code' => $employee->country->code,
                        'name' => $employee->country->name,
                        'currency_code' => $employee->country->currency_code,
                        'currency_symbol' => $employee->country->currency_symbol,
                        'flag' => $employee->country->flag,
                        'is_active' => $employee->country->is_active,
                    ] : null,
                    
                    // Manager relationship
                    'manager' => $employee->manager ? [
                        'id' => $employee->manager->id,
                        'first_name' => $employee->manager->first_name,
                        'last_name' => $employee->manager->last_name,
                        'email' => $employee->manager->email,
                    ] : null,
                    
                    // User relationship
                    'user' => [
                        'id' => $employee->user->id,
                        'first_name' => $employee->user->first_name,
                        'last_name' => $employee->user->last_name,
                        'email' => $employee->user->email,
                        'role' => $employee->user->role,
                    ],
                ];
            });
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('EMPLOYEE_CONTROLLER: Employees fetched successfully', [
                'count' => $employees->count(),
                'execution_time_ms' => $executionTime,
                'user_id' => $request->user()->id,
                'user_role' => $request->user()->role,
            ]);
            
            return response()->json([
                'data' => $transformedEmployees,
                'meta' => [
                    'total' => $transformedEmployees->count(),
                    'execution_time_ms' => $executionTime,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to fetch employees', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Failed to fetch employees',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
 * Search for users by email (Admin only)
 * Used for adding admins to businesses
 */
public function searchUsers(Request $request): JsonResponse
{
    try {
        $email = $request->query('email');
        
        if (!$email) {
            Log::warning('EMPLOYEE_CONTROLLER: User search attempted without email parameter');
            return response()->json([
                'success' => false,
                'message' => 'Email parameter is required'
            ], 422);
        }
        
        Log::info('EMPLOYEE_CONTROLLER: Searching for user by email', [
            'email' => $email,
            'requested_by' => $request->user()->id,
        ]);
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            Log::info('EMPLOYEE_CONTROLLER: User not found', [
                'email' => $email,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'User not found with this email address'
            ], 404);
        }
        
        // Load employee relationship to get additional context
        $user->load('employee');
        
        Log::info('EMPLOYEE_CONTROLLER: User found successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'has_employee_record' => $user->employee !== null,
            'current_business_id' => $user->current_business_id,
        ]);
        
        // Build response with additional context
        $userData = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role' => $user->role,
            'current_business_id' => $user->current_business_id,
        ];
        
        // Add employee-specific data if exists
        if ($user->employee) {
            $userData['employee'] = [
                'id' => $user->employee->id,
                'employee_id' => $user->employee->employee_id,
                'position' => $user->employee->position,
                'department' => $user->employee->department,
                'business_id' => $user->employee->business_id,
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => $userData
        ]);
        
    } catch (\Exception $e) {
        Log::error('EMPLOYEE_CONTROLLER: User search failed', [
            'email' => $request->query('email'),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to search for user. Please try again.'
        ], 500);
    }
}
    /**
     * Get business-scoped employees query
     */
    private function getBusinessScopedEmployees(Request $request)
    {
        $user = $request->user();
        $requestedBusinessId = $request->query('business_id');
        $query = Employee::query();

        Log::info('EMPLOYEE_CONTROLLER: Getting business scoped employees', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'user_business_id' => $user->current_business_id,
            'requested_business_id' => $requestedBusinessId,
        ]);

        // If user is admin
        if ($user->role === 'admin') {
            if ($requestedBusinessId) {
                $businessId = (int)$requestedBusinessId;
                
                if ($user->current_business_id && $user->current_business_id !== $businessId) {
                    if (!$user->businesses()->where('businesses.id', $businessId)->exists()) {
                        Log::warning('EMPLOYEE_CONTROLLER: Admin attempting to access unauthorized business', [
                            'admin_id' => $user->id,
                            'admin_business_id' => $user->current_business_id,
                            'requested_business_id' => $businessId,
                        ]);
                        $query->where('business_id', 0);
                        return $query;
                    }
                }
                
                $query->where('business_id', $businessId);
                
                Log::info('EMPLOYEE_CONTROLLER: Admin filtering by requested business', [
                    'admin_id' => $user->id,
                    'business_id' => $businessId,
                ]);
            }
            elseif ($user->current_business_id) {
                $query->where('business_id', $user->current_business_id);
                
                Log::info('EMPLOYEE_CONTROLLER: Admin filtering by current business', [
                    'admin_id' => $user->id,
                    'business_id' => $user->current_business_id,
                ]);
            } 
            elseif ($user->businesses()->exists()) {
                $businessIds = $user->businesses()->pluck('businesses.id');
                $query->whereIn('business_id', $businessIds);
                
                Log::info('EMPLOYEE_CONTROLLER: Admin filtering by managed businesses', [
                    'admin_id' => $user->id,
                    'business_ids' => $businessIds,
                ]);
            }
            else {
                Log::info('EMPLOYEE_CONTROLLER: Super admin - showing all employees', [
                    'admin_id' => $user->id,
                ]);
            }
        }
        // For managers
        elseif ($user->role === 'manager') {
            $managerEmployee = Employee::where('user_id', $user->id)->first();
            
            if ($managerEmployee && $managerEmployee->business_id) {
                $query->where('business_id', $managerEmployee->business_id)
                      ->where('manager_id', $user->id);
                      
                Log::info('EMPLOYEE_CONTROLLER: Manager filtering by business and team', [
                    'manager_id' => $user->id,
                    'business_id' => $managerEmployee->business_id,
                ]);
            } elseif ($managerEmployee) {
                $query->whereNull('business_id')
                      ->where('manager_id', $user->id);
                      
                Log::info('EMPLOYEE_CONTROLLER: Manager without business filtering team', [
                    'manager_id' => $user->id,
                ]);
            } else {
                $query->where('id', 0);
                
                Log::info('EMPLOYEE_CONTROLLER: Manager has no employee record', [
                    'manager_id' => $user->id,
                ]);
            }
        }
        // For regular employees
        elseif ($user->role === 'employee') {
            $query->where('user_id', $user->id);
            
            Log::info('EMPLOYEE_CONTROLLER: Employee viewing own record', [
                'employee_user_id' => $user->id,
            ]);
        }

        return $query;
    }

    public function managers(Request $request): JsonResponse
    {
        try {
            $currentUser = $request->user();
            $requestedBusinessId = $request->query('business_id');
            
            $query = Employee::select(['employees.*', 'users.first_name', 'users.last_name', 'users.email', 'users.role'])
                ->join('users', 'employees.user_id', '=', 'users.id')
                ->whereIn('users.role', ['manager', 'admin'])
                ->whereHas('user', function($q) {
                    $q->whereIn('role', ['manager', 'admin']);
                })
                ->with(['country']);
            
            $filterBusinessId = $requestedBusinessId ?: $currentUser->current_business_id;
            
            if ($filterBusinessId) {
                $query->where('employees.business_id', $filterBusinessId);
            } 
            elseif ($currentUser->role === 'admin' && $currentUser->businesses()->exists()) {
                $managedBusinessIds = $currentUser->businesses()->pluck('businesses.id');
                $query->whereIn('employees.business_id', $managedBusinessIds);
            }
            
            $managers = $query->get()->map(function ($employee) {
                return [
                    'id' => $employee->user_id,
                    'first_name' => $employee->user->first_name,
                    'last_name' => $employee->user->last_name,
                    'email' => $employee->user->email,
                    'department' => $employee->department,
                    'position' => $employee->position,
                    'business_id' => $employee->business_id
                ];
            });
            
            Log::info('EMPLOYEE_CONTROLLER: Managers fetched', [
                'count' => $managers->count(),
                'user_business_id' => $currentUser->current_business_id,
                'requested_business_id' => $requestedBusinessId,
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

    public function countries(): JsonResponse
    {
        try {
            $countries = Country::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'currency_code', 'currency_symbol', 'phone_code', 'flag', 'is_active']);
                
            Log::info('EMPLOYEE_CONTROLLER: Countries fetched', [
                'count' => $countries->count(),
            ]);
            
            return response()->json([
                'data' => $countries
            ]);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to fetch countries', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Failed to fetch countries'
            ], 500);
        }
    }

    public function businesses(): JsonResponse
    {
        try {
            $user = request()->user();
            $businesses = [];
            
            if ($user->role === 'admin') {
                if ($user->businesses()->exists()) {
                    $businesses = $user->businesses()
                        ->where('status', 'active')
                        ->orderBy('name')
                        ->get(['id', 'name', 'industry', 'currency_code']);
                } 
                else {
                    $businesses = \App\Models\Business::where('status', 'active')
                        ->orderBy('name')
                        ->get(['id', 'name', 'industry', 'currency_code']);
                }
            }
            
            Log::info('EMPLOYEE_CONTROLLER: Businesses fetched', [
                'count' => $businesses->count(),
                'user_id' => $user->id,
                'user_role' => $user->role,
            ]);
            
            return response()->json([
                'data' => $businesses
            ]);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to fetch businesses', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Failed to fetch businesses'
            ], 500);
        }
    }

    /**
     * Show specific employee with encrypted fields
     */
    public function show(Request $request, Employee $employee): EmployeeResource
    {
        try {
            $currentUser = $request->user();
            
            // Verify access
            if ($currentUser->role === 'admin') {
                if ($currentUser->current_business_id && 
                    $employee->business_id !== $currentUser->current_business_id) {
                    abort(403, 'Unauthorized access to employee from different business');
                }
                elseif (!$currentUser->current_business_id && $currentUser->businesses()->exists()) {
                    $managedBusinessIds = $currentUser->businesses()->pluck('businesses.id');
                    if (!$managedBusinessIds->contains($employee->business_id)) {
                        abort(403, 'Unauthorized access to employee from unmanaged business');
                    }
                }
            }
            elseif ($currentUser->role === 'manager') {
                if ($employee->manager_id !== $currentUser->id) {
                    abort(403, 'Unauthorized access to employee not in your team');
                }
            }
            elseif ($currentUser->role === 'employee') {
                if ($employee->user_id !== $currentUser->id) {
                    abort(403, 'Unauthorized access to other employee records');
                }
            }
            
            $employee->load(['user', 'manager', 'business', 'country', 'attendances', 'leaves', 'payslips']);
            return new EmployeeResource($employee);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to fetch employee details', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Store a new employee with encryption
     */
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
                
                // Optional encrypted fields
                'phone' => 'nullable|string|max:20',
                'national_id' => 'nullable|string|max:50',
                'address' => 'nullable|string',
                'emergency_contact' => 'nullable|array',
                'bank_details' => 'nullable|array',
            ]);
            
            Log::info('EMPLOYEE_CONTROLLER: Employee validation passed', [
                'email' => $validated['email'],
                'role' => $validated['role'],
                'business_id' => $validated['business_id'],
                'country_id' => $validated['country_id'],
            ]);
            
            DB::beginTransaction();
            
            $defaultPassword = $this->getDefaultPassword();
            
            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make($defaultPassword),
                'role' => $validated['role'],
                'current_business_id' => $validated['business_id'],
            ]);
            
            Log::info('EMPLOYEE_CONTROLLER: User created', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
            ]);
            
            $lastEmployee = Employee::where('business_id', $validated['business_id'])
                ->latest('id')
                ->first();
            
            $nextNumber = $lastEmployee ? (intval(substr($lastEmployee->employee_id, 3)) + 1) : 1;
            $employeeId = 'EMP' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            
            if (isset($validated['manager_id'])) {
                $managerEmployee = Employee::where('user_id', $validated['manager_id'])->first();
                if ($managerEmployee && $managerEmployee->business_id !== (int)$validated['business_id']) {
                    throw new \Exception('Manager must be in the same business');
                }
            }
            
            // Create employee - encrypted fields will be auto-encrypted by trait
            $employee = Employee::create([
                'user_id' => $user->id,
                'business_id' => $validated['business_id'],
                'country_id' => $validated['country_id'],
                'manager_id' => ($validated['role'] === 'employee') ? $validated['manager_id'] : null,
                'employee_id' => $employeeId,
                'position' => $validated['position'],
                'department' => $validated['department'],
                'base_salary' => $validated['base_salary'],
                'transport_allowance' => $validated['transport_allowance'],
                'lunch_allowance' => $validated['lunch_allowance'],
                'hire_date' => $validated['hire_date'],
                'employment_type' => $validated['employment_type'],
                
                // Encrypted fields
                'phone' => $validated['phone'] ?? null,
                'national_id' => $validated['national_id'] ?? null,
                'address' => $validated['address'] ?? null,
                'emergency_contact' => $validated['emergency_contact'] ?? null,
                'bank_details' => $validated['bank_details'] ?? null,
            ]);
            
            if ($validated['role'] === 'manager' || $validated['role'] === 'admin') {
                Manager::create([
                    'user_id' => $user->id,
                    'department' => $validated['department'],
                    'max_team_size' => 10,
                    'permissions' => json_encode([]),
                    'business_id' => $validated['business_id'],
                    'country_id' => $validated['country_id'],
                ]);
                
                Log::info('EMPLOYEE_CONTROLLER: Manager record created', [
                    'user_id' => $user->id,
                    'department' => $validated['department'],
                ]);
            }
            
            DB::commit();
            
            Log::info('EMPLOYEE_CONTROLLER: Employee created successfully', [
                'employee_id' => $employee->id,
                'employee_number' => $employeeId,
                'role' => $validated['role'],
            ]);
            
            $employee->load(['user', 'manager', 'business', 'country']);
            
            return response()->json([
                'employee' => new EmployeeResource($employee),
                'message' => 'Employee created successfully.',
                'credentials' => [
                    'email' => $user->email,
                    'password' => $defaultPassword,
                    'note' => 'Use these credentials to login. Please change password after first login.'
                ]
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            Log::warning('EMPLOYEE_CONTROLLER: Employee validation failed', [
                'errors' => $e->errors(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('EMPLOYEE_CONTROLLER: Failed to create employee', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update employee with encryption support
     */
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
                
                // Encrypted fields
                'phone' => 'nullable|string|max:20',
                'national_id' => 'nullable|string|max:50',
                'address' => 'nullable|string',
                'emergency_contact' => 'nullable|array',
                'bank_details' => 'nullable|array',
            ]);
            
            DB::beginTransaction();
            
            $userUpdates = [];
            $employeeUpdates = [];
            
            $oldRole = $employee->user->role;
            $newRole = $validated['role'] ?? $oldRole;
            
            // Update user details
            if (isset($validated['first_name'])) $userUpdates['first_name'] = $validated['first_name'];
            if (isset($validated['last_name'])) $userUpdates['last_name'] = $validated['last_name'];
            if (isset($validated['email'])) $userUpdates['email'] = $validated['email'];
            if (isset($validated['role'])) $userUpdates['role'] = $validated['role'];
            
            if (isset($validated['business_id'])) {
                $userUpdates['current_business_id'] = $validated['business_id'];
                $employeeUpdates['business_id'] = $validated['business_id'];
            }
            
            if (isset($validated['country_id'])) {
                $employeeUpdates['country_id'] = $validated['country_id'];
            }
            
            if (!empty($userUpdates)) {
                $employee->user->update($userUpdates);
            }
            
            // Handle role changes
            if ($oldRole !== $newRole) {
                if (($newRole === 'manager' || $newRole === 'admin') &&
                    ($oldRole !== 'manager' && $oldRole !== 'admin')) {
                    Manager::firstOrCreate([
                        'user_id' => $employee->user_id,
                    ], [
                        'department' => $validated['department'] ?? $employee->department,
                        'max_team_size' => 10,
                        'permissions' => json_encode([]),
                        'business_id' => $validated['business_id'] ?? $employee->business_id,
                        'country_id' => $validated['country_id'] ?? $employee->country_id,
                    ]);
                    $employeeUpdates['manager_id'] = null;
                }
                elseif ($newRole === 'employee' &&
                        ($oldRole === 'manager' || $oldRole === 'admin')) {
                    Manager::where('user_id', $employee->user_id)->delete();
                    if (isset($validated['manager_id'])) {
                        $employeeUpdates['manager_id'] = $validated['manager_id'];
                    }
                }
            }
            
            // Update employee fields
            $employeeFields = [
                'position', 'department', 'base_salary', 'transport_allowance',
                'lunch_allowance', 'employment_type', 'business_id', 'country_id',
                // Encrypted fields
                'phone', 'national_id', 'address', 'emergency_contact', 'bank_details'
            ];
            
            foreach ($employeeFields as $field) {
                if (isset($validated[$field])) {
                    $employeeUpdates[$field] = $validated[$field];
                }
            }
            
            // Handle manager_id for employees
            if ($newRole === 'employee' && isset($validated['manager_id'])) {
                $employeeUpdates['manager_id'] = $validated['manager_id'];
            } elseif ($newRole !== 'employee') {
                $employeeUpdates['manager_id'] = null;
            }
            
            if (!empty($employeeUpdates)) {
                $employee->update($employeeUpdates);
            }
            
            DB::commit();
            
            $employee->load(['user', 'manager', 'business', 'country']);
            
            return response()->json([
                'employee' => new EmployeeResource($employee),
                'message' => 'Employee updated successfully'
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
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
        try {
            DB::beginTransaction();
            $user = $employee->user;
            
            if ($user->role === 'manager' || $user->role === 'admin') {
                Manager::where('user_id', $user->id)->delete();
            }
            
            $employee->delete();
            
            if ($user) {
                $user->delete();
            }
            
            DB::commit();
            
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

    public function search(Request $request): AnonymousResourceCollection
    {
        $searchTerm = $request->query('q');
        try {
            $query = $this->getBusinessScopedEmployees($request)
                ->whereHas('user', function($q) use ($searchTerm) {
                    $q->where('first_name', 'like', "%{$searchTerm}%")
                      ->orWhere('last_name', 'like', "%{$searchTerm}%")
                      ->orWhere('email', 'like', "%{$searchTerm}%");
                });
            
            $employees = $query->get();
            
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
    
/**
 * Get all available departments from system settings
 */
public function departments(): JsonResponse
{
    try {
        $departmentsSetting = \App\Models\SystemSetting::where('key', 'departments')->first();
        
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

        Log::info('EMPLOYEE_CONTROLLER: Departments fetched', [
            'count' => count($departmentList),
        ]);

        return response()->json([
            'data' => $departmentList
        ]);
    } catch (\Exception $e) {
        Log::error('EMPLOYEE_CONTROLLER: Failed to fetch departments', [
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
        
        $manager = User::findOrFail($managerId);
        $managerEmployee = Employee::where('user_id', $managerId)->first();
        
        if ($managerEmployee && $managerEmployee->business_id) {
            $employees = Employee::where('business_id', $managerEmployee->business_id)
                                 ->where('manager_id', $managerId)
                                 ->with(['user'])
                                 ->get();
        } else {
            $employees = Employee::whereNull('business_id')
                                 ->where('manager_id', $managerId)
                                 ->with(['user'])
                                 ->get();
        }
        
        return EmployeeResource::collection($employees);
    }

     /**
     * Get authenticated user's profile with encrypted fields
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        try {
            $employee = $user->employee;
            if ($employee) {
                $employee->load('manager');
            }
            
            Log::info('EMPLOYEE_CONTROLLER: Profile fetched', [
                'user_id' => $user->id,
                'role' => $user->role,
                'has_employee_record' => $employee !== null,
            ]);
            
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'current_business_id' => $user->current_business_id,
                ],
                'employee' => $employee ? [
                    'id' => $employee->id,
                    'employee_id' => $employee->employee_id,
                    'position' => $employee->position,
                    'department' => $employee->department,
                    'phone' => $employee->phone, // Auto-decrypts
                    'national_id' => $employee->national_id, // Auto-decrypts
                    'address' => $employee->address, // Auto-decrypts
                    'emergency_contact' => $employee->emergency_contact, // Auto-decrypts
                    'bank_details' => $employee->bank_details, // Auto-decrypts
                    'profile_pic' => $employee->profile_pic,
                ] : null,
            ]);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to fetch profile', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Failed to load profile data'
            ], 500);
        }
    }

    /**
     * Update profile with encrypted fields
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        try {
            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'phone' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date|before:today',
                'national_id' => 'nullable|string|max:50',
                'address' => 'nullable|string|max:500',
                'emergency_contact' => 'nullable|array',
                'bank_details' => 'nullable|array',
            ]);
            
            Log::info('EMPLOYEE_CONTROLLER: Updating profile', [
                'user_id' => $user->id,
                'role' => $user->role,
                'fields' => array_keys($validated),
            ]);
            
            DB::beginTransaction();
            
            // Update user table fields
            $userUpdates = [];
            if (isset($validated['first_name'])) $userUpdates['first_name'] = $validated['first_name'];
            if (isset($validated['last_name'])) $userUpdates['last_name'] = $validated['last_name'];
            if (isset($validated['email'])) $userUpdates['email'] = $validated['email'];
            
            if (!empty($userUpdates)) {
                $user->update($userUpdates);
            }
            
            // Update employee table fields (will auto-encrypt)
            $employee = $user->employee;
            if ($employee) {
                $employeeUpdates = [];
                if (isset($validated['phone'])) $employeeUpdates['phone'] = $validated['phone'];
                if (isset($validated['date_of_birth'])) $employeeUpdates['date_of_birth'] = $validated['date_of_birth'];
                if (isset($validated['national_id'])) $employeeUpdates['national_id'] = $validated['national_id'];
                if (isset($validated['address'])) $employeeUpdates['address'] = $validated['address'];
                if (isset($validated['emergency_contact'])) $employeeUpdates['emergency_contact'] = $validated['emergency_contact'];
                if (isset($validated['bank_details'])) $employeeUpdates['bank_details'] = $validated['bank_details'];
                
                if (!empty($employeeUpdates)) {
                    $employee->update($employeeUpdates);
                }
            }
            
            DB::commit();
            
            // Refresh data
            $user->refresh();
            if ($employee) {
                $employee->refresh();
                $employee->load('manager');
            }
            
            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'current_business_id' => $user->current_business_id,
                ],
                'employee' => $employee ? [
                    'id' => $employee->id,
                    'employee_id' => $employee->employee_id,
                    'phone' => $employee->phone,
                    'national_id' => $employee->national_id,
                    'address' => $employee->address,
                    'emergency_contact' => $employee->emergency_contact,
                    'bank_details' => $employee->bank_details,
                    'profile_pic' => $employee->profile_pic,
                ] : null,
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            Log::warning('EMPLOYEE_CONTROLLER: Profile update validation failed', [
                'user_id' => $user->id,
                'errors' => $e->errors(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('EMPLOYEE_CONTROLLER: Failed to update profile', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Failed to update profile. Please try again.'
            ], 500);
        }
    }

    /**
     * Get default password from system settings
     */
    private function getDefaultPassword(): string
    {
        try {
            $defaultPasswordSetting = SystemSetting::where('key', 'default_password')->first();
            
            if (!$defaultPasswordSetting || empty($defaultPasswordSetting->value)) {
                Log::warning('EMPLOYEE_CONTROLLER: No default password found, using fallback');
                return 'Password123!';
            }
            
            $password = trim($defaultPasswordSetting->value);
            
            if (strlen($password) < 8) {
                Log::warning('EMPLOYEE_CONTROLLER: Default password too short, using fallback');
                return 'Password123!';
            }
               return $password;
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to get default password', [
                'error' => $e->getMessage(),
            ]);
            return 'Password123!';
        }
    }

    /**
     * Update user password (accessible to ALL roles)
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();
        try {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'password' => ['required', 'confirmed', Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                ],
            ]);
            Log::info('EMPLOYEE_CONTROLLER: Password update requested', [
                'user_id' => $user->id,
                'role' => $user->role,
            ]);
            // Verify current password
            if (!Hash::check($validated['current_password'], $user->password)) {
                Log::warning('EMPLOYEE_CONTROLLER: Incorrect current password', [
                    'user_id' => $user->id,
                ]);
                throw ValidationException::withMessages([
                    'current_password' => ['The provided password does not match your current password.'],
                ]);
            }
            // Update password
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
            Log::info('EMPLOYEE_CONTROLLER: Password updated successfully', [
                'user_id' => $user->id,
            ]);
            return response()->json([
                'message' => 'Password updated successfully'
            ]);
        } catch (ValidationException $e) {
            Log::warning('EMPLOYEE_CONTROLLER: Password update validation failed', [
                'user_id' => $user->id,
                'errors' => $e->errors(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to update password', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Failed to update password. Please try again.'
            ], 500);
        }
    }

    public function documents(Request $request): JsonResponse
    {
        try {
            $user = $request->user();  // Now accessible
            $employee = $user->employee;
            if (!$employee) {
                return response()->json(['message' => 'Employee profile not found'], 404);
            }
            $documents = Document::where('employee_id', $employee->id)->get();
            Log::info('EMPLOYEE_CONTROLLER: Documents fetched', [
                'user_id' => $user->id,
                'count' => $documents->count(),
            ]);
            return response()->json([
                'data' => $documents,
            ]);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to fetch documents', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Failed to fetch documents'
            ], 500);
        }
    }

    public function uploadDocuments(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;
        if (!$employee) {
            return response()->json(['message' => 'Employee profile not found'], 404);
        }
        $request->validate([
            'documents' => 'required|array|max:10',
            'documents.*' => 'file|mimes:pdf,doc,docx,jpg,png|max:5120', // 5MB max
        ]);
        try {
            DB::beginTransaction();
            $newDocuments = [];
            foreach ($request->file('documents') as $file) {
                $path = $file->store('documents', 'public');
                $document = Document::create([
                    'employee_id' => $employee->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'type' => $request->input('type', 'general'), // Optional type
                    'size' => $file->getSize(),
                ]);
                $newDocuments[] = $document;
            }
            DB::commit();
            Log::info('EMPLOYEE_CONTROLLER: Documents uploaded', [
                'user_id' => $user->id,
                'count' => count($newDocuments),
            ]);
            return response()->json([
                'message' => 'Documents uploaded successfully',
                'newDocuments' => $newDocuments,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('EMPLOYEE_CONTROLLER: Failed to upload documents', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Failed to upload documents'
            ], 500);
        }
    }

    public function deleteDocument($id): JsonResponse
    {
        try {
            $document = Document::findOrFail($id);
            // Check ownership
            $user = $request->user();
            $employee = $user->employee;
            if ($document->employee_id !== $employee->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            // Delete file
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
            $document->delete();
            Log::info('EMPLOYEE_CONTROLLER: Document deleted', [
                'document_id' => $id,
                'user_id' => $user->id,
            ]);
            return response()->json(['message' => 'Document deleted successfully']);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to delete document', [
                'document_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Failed to delete document'
            ], 500);
        }
    }

    public function downloadDocument($id)
    {
        try {
            $document = Document::findOrFail($id);
            // Check ownership
            $user = $request->user();
            $employee = $user->employee;
            if ($document->employee_id !== $employee->id) {
                abort(403, 'Unauthorized');
            }
            if (!Storage::disk('public')->exists($document->file_path)) {
                abort(404, 'File not found');
            }
            return Storage::disk('public')->download($document->file_path, $document->file_name);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to download document', [
                'document_id' => $id,
                'error' => $e->getMessage(),
            ]);
            abort(500, 'Failed to download document');
        }
    }

    public function uploadProfilePic(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;
        if (!$employee) {
            return response()->json(['message' => 'Employee profile not found'], 404);
        }
        $request->validate([
            'profile_pic' => 'required|image|mimes:jpeg,png,jpg|max:2048', // 2MB max
        ]);
        try {
            $file = $request->file('profile_pic');
            $path = $file->store('profile_pics', 'public');
            $employee->update(['profile_pic' => $path]);
            Log::info('EMPLOYEE_CONTROLLER: Profile pic uploaded', [
                'user_id' => $user->id,
            ]);
            return response()->json([
                'message' => 'Profile picture updated successfully',
                'profile_pic_url' => Storage::url($path),
            ]);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to upload profile pic', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Failed to upload profile picture'
            ], 500);
        }
    }
  
}