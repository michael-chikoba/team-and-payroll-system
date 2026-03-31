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

    // ─────────────────────────────────────────────────────────────
    // INDEX  – active + suspended employees (not archived)
    // ─────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        try {
            $startTime = microtime(true);

            $employees = $this->getBusinessScopedEmployees($request)
                ->notArchived()
                ->with(['user', 'manager', 'business', 'country'])
                ->get();

            Log::info('EMPLOYEE_CONTROLLER: Raw employees data', [
                'count'           => $employees->count(),
                'business_filter' => $request->query('business_id'),
            ]);

            $transformedEmployees = $this->transformEmployees($employees);
            $executionTime        = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('EMPLOYEE_CONTROLLER: Employees fetched successfully', [
                'count'             => $employees->count(),
                'execution_time_ms' => $executionTime,
                'user_id'           => $request->user()->id,
                'user_role'         => $request->user()->role,
            ]);

            return response()->json([
                'data' => $transformedEmployees,
                'meta' => [
                    'total'             => $transformedEmployees->count(),
                    'execution_time_ms' => $executionTime,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to fetch employees', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Failed to fetch employees',
                'error'   => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // ARCHIVED EMPLOYEES
    // ─────────────────────────────────────────────────────────────

    public function archived(Request $request): JsonResponse
    {
        try {
            $startTime = microtime(true);

            $employees = $this->getBusinessScopedEmployees($request)
                ->archived()
                ->with(['user', 'manager', 'business', 'country', 'archivedByUser', 'suspendedByUser'])
                ->get();

            $transformedEmployees = $this->transformEmployees($employees, true);
            $executionTime        = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('EMPLOYEE_CONTROLLER: Archived employees fetched', [
                'count'             => $employees->count(),
                'execution_time_ms' => $executionTime,
            ]);

            return response()->json([
                'data' => $transformedEmployees,
                'meta' => [
                    'total'             => $transformedEmployees->count(),
                    'execution_time_ms' => $executionTime,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to fetch archived employees', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to fetch archived employees'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // SUSPEND
    // ─────────────────────────────────────────────────────────────

    public function suspend(Request $request, Employee $employee): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reason' => 'required|string|max:500',
                'notes'  => 'nullable|string|max:1000',
            ]);

            if ($employee->isArchived()) {
                return response()->json([
                    'message' => 'Cannot suspend an archived employee. Reinstate them first.',
                ], 422);
            }

            if ($employee->isSuspended()) {
                return response()->json(['message' => 'Employee is already suspended.'], 422);
            }

            DB::beginTransaction();

            $employee->update([
                'status'            => 'suspended',
                'suspended_at'      => now(),
                'suspended_by'      => $request->user()->id,
                'suspension_reason' => $validated['reason'],
                'status_notes'      => $validated['notes'] ?? null,
            ]);

            $employee->user?->update(['account_status' => 'suspended']);
            $employee->user?->tokens()->delete();

            DB::commit();

            Log::info('EMPLOYEE_CONTROLLER: Employee suspended', [
                'employee_id' => $employee->id,
                'by_user'     => $request->user()->id,
                'reason'      => $validated['reason'],
            ]);

            return response()->json([
                'message'  => 'Employee suspended successfully.',
                'employee' => $this->buildEmployeePayload($employee->fresh(['user', 'manager', 'business', 'country'])),
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('EMPLOYEE_CONTROLLER: Failed to suspend employee', [
                'employee_id' => $employee->id,
                'error'       => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to suspend employee.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // ARCHIVE
    // ─────────────────────────────────────────────────────────────

    public function archive(Request $request, Employee $employee): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reason' => 'required|string|max:500',
                'notes'  => 'nullable|string|max:1000',
            ]);

            if ($employee->isArchived()) {
                return response()->json(['message' => 'Employee is already archived.'], 422);
            }

            DB::beginTransaction();

            $employee->update([
                'status'         => 'archived',
                'archived_at'    => now(),
                'archived_by'    => $request->user()->id,
                'archive_reason' => $validated['reason'],
                'status_notes'   => $validated['notes'] ?? $employee->status_notes,
            ]);

            $employee->user?->update(['account_status' => 'archived']);
            $employee->user?->tokens()->delete();

            DB::commit();

            Log::info('EMPLOYEE_CONTROLLER: Employee archived', [
                'employee_id' => $employee->id,
                'by_user'     => $request->user()->id,
                'reason'      => $validated['reason'],
            ]);

            return response()->json([
                'message'  => 'Employee archived successfully.',
                'employee' => $this->buildEmployeePayload($employee->fresh(['user', 'manager', 'business', 'country'])),
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('EMPLOYEE_CONTROLLER: Failed to archive employee', [
                'employee_id' => $employee->id,
                'error'       => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to archive employee.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // REINSTATE
    // ─────────────────────────────────────────────────────────────

    public function reinstate(Request $request, Employee $employee): JsonResponse
    {
        try {
            $validated = $request->validate([
                'notes' => 'nullable|string|max:1000',
            ]);

            if ($employee->isActive()) {
                return response()->json(['message' => 'Employee is already active.'], 422);
            }

            DB::beginTransaction();

            $employee->update([
                'status'        => 'active',
                'reinstated_at' => now(),
                'reinstated_by' => $request->user()->id,
                'status_notes'  => $validated['notes'] ?? null,
            ]);

            $employee->user?->update(['account_status' => 'active']);

            DB::commit();

            Log::info('EMPLOYEE_CONTROLLER: Employee reinstated', [
                'employee_id' => $employee->id,
                'by_user'     => $request->user()->id,
            ]);

            return response()->json([
                'message'  => 'Employee reinstated successfully.',
                'employee' => $this->buildEmployeePayload($employee->fresh(['user', 'manager', 'business', 'country'])),
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('EMPLOYEE_CONTROLLER: Failed to reinstate employee', [
                'employee_id' => $employee->id,
                'error'       => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to reinstate employee.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // SHOW
    // ─────────────────────────────────────────────────────────────

    public function show(Request $request, Employee $employee): EmployeeResource
    {
        try {
            $currentUser = $request->user();

            if ($currentUser->role === 'admin') {
                if ($currentUser->current_business_id &&
                    $employee->business_id !== $currentUser->current_business_id) {
                    abort(403, 'Unauthorized access to employee from different business');
                } elseif (!$currentUser->current_business_id && $currentUser->businesses()->exists()) {
                    $managedBusinessIds = $currentUser->businesses()->pluck('businesses.id');
                    if (!$managedBusinessIds->contains($employee->business_id)) {
                        abort(403, 'Unauthorized access to employee from unmanaged business');
                    }
                }
            } elseif ($currentUser->role === 'manager') {
                if ($employee->manager_id !== $currentUser->id) {
                    abort(403, 'Unauthorized access to employee not in your team');
                }
            } elseif ($currentUser->role === 'employee') {
                if ($employee->user_id !== $currentUser->id) {
                    abort(403, 'Unauthorized access to other employee records');
                }
            }

            $employee->load(['user', 'manager', 'business', 'country', 'attendances', 'leaves', 'payslips']);
            return new EmployeeResource($employee);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to fetch employee details', [
                'employee_id' => $employee->id,
                'error'       => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // STORE
    // ─────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_name'          => 'required|string|max:255',
                'last_name'           => 'required|string|max:255',
                'email'               => 'required|email|unique:users,email',
                'role'                => 'required|in:employee,manager,admin',
                'position'            => 'required|string|max:255',
                'department'          => 'required|string|max:255',
                'base_salary'         => 'required|numeric|min:0',
                'transport_allowance' => 'required|numeric|min:0',
                'lunch_allowance'     => 'required|numeric|min:0',
                'hire_date'           => 'required|date',
                'employment_type'     => 'required|in:full_time,part_time,contract',
                'manager_id'          => 'nullable|exists:users,id',
                'business_id'         => 'required|exists:businesses,id',
                'country_id'          => 'required|exists:countries,id',
                'phone'               => 'nullable|string|max:20',
                'national_id'         => 'nullable|string|max:50',
                'address'             => 'nullable|string',
                'emergency_contact'   => 'nullable|array',
                'bank_details'        => 'nullable|array',
            ]);

            Log::info('EMPLOYEE_CONTROLLER: Employee validation passed', [
                'email'       => $validated['email'],
                'role'        => $validated['role'],
                'business_id' => $validated['business_id'],
                'country_id'  => $validated['country_id'],
            ]);

            DB::beginTransaction();

            $defaultPassword = $this->getDefaultPassword();

            $user = User::create([
                'first_name'          => $validated['first_name'],
                'last_name'           => $validated['last_name'],
                'email'               => $validated['email'],
                'password'            => Hash::make($defaultPassword),
                'role'                => $validated['role'],
                'current_business_id' => $validated['business_id'],
                'account_status'      => 'active',
            ]);

            Log::info('EMPLOYEE_CONTROLLER: User created', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'role'    => $user->role,
            ]);

            $lastEmployee = Employee::where('business_id', $validated['business_id'])->latest('id')->first();
            $nextNumber   = $lastEmployee ? (intval(substr($lastEmployee->employee_id, 3)) + 1) : 1;
            $employeeId   = 'EMP' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            if (isset($validated['manager_id'])) {
                $managerEmployee = Employee::where('user_id', $validated['manager_id'])->first();
                if ($managerEmployee && $managerEmployee->business_id !== (int) $validated['business_id']) {
                    throw new \Exception('Manager must be in the same business');
                }
            }

            $employee = Employee::create([
                'user_id'             => $user->id,
                'business_id'         => $validated['business_id'],
                'country_id'          => $validated['country_id'],
                'manager_id'          => ($validated['role'] === 'employee') ? $validated['manager_id'] : null,
                'employee_id'         => $employeeId,
                'position'            => $validated['position'],
                'department'          => $validated['department'],
                'base_salary'         => $validated['base_salary'],
                'transport_allowance' => $validated['transport_allowance'],
                'lunch_allowance'     => $validated['lunch_allowance'],
                'hire_date'           => $validated['hire_date'],
                'employment_type'     => $validated['employment_type'],
                'status'              => 'active',
                'phone'               => $validated['phone']             ?? null,
                'national_id'         => $validated['national_id']       ?? null,
                'address'             => $validated['address']           ?? null,
                'emergency_contact'   => $validated['emergency_contact'] ?? null,
                'bank_details'        => $validated['bank_details']      ?? null,
            ]);

            if (in_array($validated['role'], ['manager', 'admin'])) {
                Manager::create([
                    'user_id'       => $user->id,
                    'department'    => $validated['department'],
                    'max_team_size' => 10,
                    'permissions'   => json_encode([]),
                    'business_id'   => $validated['business_id'],
                    'country_id'    => $validated['country_id'],
                ]);

                Log::info('EMPLOYEE_CONTROLLER: Manager record created', [
                    'user_id'    => $user->id,
                    'department' => $validated['department'],
                ]);
            }

            DB::commit();

            Log::info('EMPLOYEE_CONTROLLER: Employee created successfully', [
                'employee_id'     => $employee->id,
                'employee_number' => $employeeId,
                'role'            => $validated['role'],
            ]);

            $employee->load(['user', 'manager', 'business', 'country']);

            return response()->json([
                'employee'    => new EmployeeResource($employee),
                'message'     => 'Employee created successfully.',
                'credentials' => [
                    'email'    => $user->email,
                    'password' => $defaultPassword,
                    'note'     => 'Use these credentials to login. Please change password after first login.',
                ],
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            Log::warning('EMPLOYEE_CONTROLLER: Employee validation failed', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('EMPLOYEE_CONTROLLER: Failed to create employee', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // UPDATE
    // ─────────────────────────────────────────────────────────────

    public function update(Request $request, Employee $employee): JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_name'          => 'sometimes|string|max:255',
                'last_name'           => 'sometimes|string|max:255',
                'email'               => 'sometimes|email|unique:users,email,' . $employee->user_id,
                'role'                => 'sometimes|in:employee,manager,admin',
                'position'            => 'sometimes|string|max:255',
                'department'          => 'sometimes|string|max:255',
                'base_salary'         => 'sometimes|numeric|min:0',
                'transport_allowance' => 'sometimes|numeric|min:0',
                'lunch_allowance'     => 'sometimes|numeric|min:0',
                'employment_type'     => 'sometimes|in:full_time,part_time,contract',
                'manager_id'          => 'nullable|exists:users,id',
                'business_id'         => 'sometimes|exists:businesses,id',
                'country_id'          => 'sometimes|exists:countries,id',
                'phone'               => 'nullable|string|max:20',
                'national_id'         => 'nullable|string|max:50',
                'address'             => 'nullable|string',
                'emergency_contact'   => 'nullable|array',
                'bank_details'        => 'nullable|array',
            ]);

            DB::beginTransaction();

            $userUpdates     = [];
            $employeeUpdates = [];
            $oldRole         = $employee->user->role;
            $newRole         = $validated['role'] ?? $oldRole;

            if (isset($validated['first_name'])) $userUpdates['first_name'] = $validated['first_name'];
            if (isset($validated['last_name']))  $userUpdates['last_name']  = $validated['last_name'];
            if (isset($validated['email']))      $userUpdates['email']      = $validated['email'];
            if (isset($validated['role']))       $userUpdates['role']       = $validated['role'];

            if (isset($validated['business_id'])) {
                $userUpdates['current_business_id'] = $validated['business_id'];
                $employeeUpdates['business_id']     = $validated['business_id'];
            }
            if (isset($validated['country_id'])) {
                $employeeUpdates['country_id'] = $validated['country_id'];
            }

            if (!empty($userUpdates)) {
                $employee->user->update($userUpdates);
            }

            if ($oldRole !== $newRole) {
                if (in_array($newRole, ['manager', 'admin']) && !in_array($oldRole, ['manager', 'admin'])) {
                    Manager::firstOrCreate(
                        ['user_id' => $employee->user_id],
                        [
                            'department'    => $validated['department'] ?? $employee->department,
                            'max_team_size' => 10,
                            'permissions'   => json_encode([]),
                            'business_id'   => $validated['business_id'] ?? $employee->business_id,
                            'country_id'    => $validated['country_id']  ?? $employee->country_id,
                        ]
                    );
                    $employeeUpdates['manager_id'] = null;
                } elseif ($newRole === 'employee' && in_array($oldRole, ['manager', 'admin'])) {
                    Manager::where('user_id', $employee->user_id)->delete();
                    if (isset($validated['manager_id'])) {
                        $employeeUpdates['manager_id'] = $validated['manager_id'];
                    }
                }
            }

            $employeeFields = [
                'position', 'department', 'base_salary', 'transport_allowance',
                'lunch_allowance', 'employment_type', 'business_id', 'country_id',
                'phone', 'national_id', 'address', 'emergency_contact', 'bank_details',
            ];

            foreach ($employeeFields as $field) {
                if (isset($validated[$field])) {
                    $employeeUpdates[$field] = $validated[$field];
                }
            }

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
                'message'  => 'Employee updated successfully',
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('EMPLOYEE_CONTROLLER: Failed to update employee', [
                'employee_id' => $employee->id,
                'error'       => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to update employee.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // DESTROY  – hard-delete for super-admin only (GDPR erasure).
    //            In normal flow, use archive instead.
    // ─────────────────────────────────────────────────────────────

    public function destroy(Request $request, Employee $employee): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Permanent deletion is not allowed. Use Archive instead.',
            ], 403);
        }

        try {
            DB::beginTransaction();
            $user = $employee->user;

            if ($user && in_array($user->role, ['manager', 'admin'])) {
                Manager::where('user_id', $user->id)->delete();
            }

            $employee->delete();

            if ($user) {
                $user->delete();
            }

            DB::commit();

            return response()->json(['message' => 'Employee permanently deleted.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('EMPLOYEE_CONTROLLER: Failed to delete employee', [
                'employee_id' => $employee->id,
                'error'       => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to delete employee.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // SEARCH USERS (admin only)
    // ─────────────────────────────────────────────────────────────

    public function searchUsers(Request $request): JsonResponse
    {
        try {
            $email = $request->query('email');

            if (!$email) {
                return response()->json(['success' => false, 'message' => 'Email parameter is required'], 422);
            }

            Log::info('EMPLOYEE_CONTROLLER: Searching for user by email', [
                'email'        => $email,
                'requested_by' => $request->user()->id,
            ]);

            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found with this email address'], 404);
            }

            $user->load('employee');

            $userData = [
                'id'                  => $user->id,
                'first_name'          => $user->first_name,
                'last_name'           => $user->last_name,
                'email'               => $user->email,
                'role'                => $user->role,
                'account_status'      => $user->account_status,
                'current_business_id' => $user->current_business_id,
            ];

            if ($user->employee) {
                $userData['employee'] = [
                    'id'          => $user->employee->id,
                    'employee_id' => $user->employee->employee_id,
                    'position'    => $user->employee->position,
                    'department'  => $user->employee->department,
                    'business_id' => $user->employee->business_id,
                    'status'      => $user->employee->status,
                ];
            }

            return response()->json(['success' => true, 'data' => $userData]);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: User search failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to search for user.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // SUPPORTING ENDPOINTS
    // ─────────────────────────────────────────────────────────────

    public function managers(Request $request): JsonResponse
    {
        try {
            $currentUser         = $request->user();
            $requestedBusinessId = $request->query('business_id');

            $query = Employee::select(['employees.*', 'users.first_name', 'users.last_name', 'users.email', 'users.role'])
                ->join('users', 'employees.user_id', '=', 'users.id')
                ->whereIn('users.role', ['manager', 'admin'])
                ->whereHas('user', fn($q) => $q->whereIn('role', ['manager', 'admin']))
                ->where('employees.status', 'active')
                ->with(['country']);

            $filterBusinessId = $requestedBusinessId ?: $currentUser->current_business_id;

            if ($filterBusinessId) {
                $query->where('employees.business_id', $filterBusinessId);
            } elseif ($currentUser->role === 'admin' && $currentUser->businesses()->exists()) {
                $query->whereIn('employees.business_id', $currentUser->businesses()->pluck('businesses.id'));
            }

            $managers = $query->get()->map(fn($e) => [
                'id'          => $e->user_id,
                'first_name'  => $e->user->first_name,
                'last_name'   => $e->user->last_name,
                'email'       => $e->user->email,
                'department'  => $e->department,
                'position'    => $e->position,
                'business_id' => $e->business_id,
            ]);

            Log::info('EMPLOYEE_CONTROLLER: Managers fetched', [
                'count'                  => $managers->count(),
                'user_business_id'       => $currentUser->current_business_id,
                'requested_business_id'  => $requestedBusinessId,
            ]);

            return response()->json($managers);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to fetch managers', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to fetch managers list'], 500);
        }
    }

    public function countries(): JsonResponse
    {
        try {
            $countries = Country::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'currency_code', 'currency_symbol', 'phone_code', 'flag', 'is_active']);

            Log::info('EMPLOYEE_CONTROLLER: Countries fetched', ['count' => $countries->count()]);

            return response()->json(['data' => $countries]);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to fetch countries', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to fetch countries'], 500);
        }
    }

    public function businesses(): JsonResponse
    {
        try {
            $user       = request()->user();
            $businesses = [];

            if ($user->role === 'admin') {
                $businesses = $user->businesses()->exists()
                    ? $user->businesses()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'industry', 'currency_code'])
                    : \App\Models\Business::where('status', 'active')->orderBy('name')->get(['id', 'name', 'industry', 'currency_code']);
            }

            Log::info('EMPLOYEE_CONTROLLER: Businesses fetched', [
                'count'     => is_countable($businesses) ? count($businesses) : 0,
                'user_id'   => $user->id,
                'user_role' => $user->role,
            ]);

            return response()->json(['data' => $businesses]);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to fetch businesses', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to fetch businesses'], 500);
        }
    }

    public function search(Request $request): AnonymousResourceCollection
    {
        $searchTerm = $request->query('q');
        try {
            $query = $this->getBusinessScopedEmployees($request)
                ->notArchived()
                ->whereHas('user', function ($q) use ($searchTerm) {
                    $q->where('first_name', 'like', "%{$searchTerm}%")
                      ->orWhere('last_name',  'like', "%{$searchTerm}%")
                      ->orWhere('email',      'like', "%{$searchTerm}%");
                });

            return EmployeeResource::collection($query->get());
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Employee search failed', [
                'search_term' => $searchTerm,
                'error'       => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function health(): JsonResponse
    {
        Log::info('EMPLOYEE_CONTROLLER: Health check requested');
        try {
            DB::select('SELECT 1');
            return response()->json([
                'status'          => 'healthy',
                'user_count'      => User::count(),
                'employee_count'  => Employee::count(),
                'database_status' => 'connected',
                'timestamp'       => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Health check failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status'    => 'unhealthy',
                'error'     => $e->getMessage(),
                'timestamp' => now()->toISOString(),
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
                'message'  => 'Leave balances initialized successfully',
                'balances' => LeaveBalance::where('employee_id', $employee->id)->get(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to initialize leave balances',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function departments(): JsonResponse
    {
        try {
            $departmentsSetting = \App\Models\SystemSetting::where('key', 'departments')->first();

            if (!$departmentsSetting) {
                return response()->json(['data' => []]);
            }

            $departments    = json_decode($departmentsSetting->value, true) ?? [];
            $departmentList = array_map(fn($d) => $d['name'], $departments);

            Log::info('EMPLOYEE_CONTROLLER: Departments fetched', ['count' => count($departmentList)]);

            return response()->json(['data' => $departmentList]);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to fetch departments', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to fetch departments'], 500);
        }
    }

    public function teamEmployees(Request $request): AnonymousResourceCollection
    {
        $managerId = $request->query('manager_id');
        if (!$managerId) abort(400, 'Manager ID required');

        User::findOrFail($managerId);
        $managerEmployee = Employee::where('user_id', $managerId)->first();

        $query = Employee::where('manager_id', $managerId)->notArchived()->with(['user']);

        if ($managerEmployee && $managerEmployee->business_id) {
            $query->where('business_id', $managerEmployee->business_id);
        } else {
            $query->whereNull('business_id');
        }

        return EmployeeResource::collection($query->get());
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        try {
            $employee = $user->employee;
            if ($employee) {
                $employee->load('manager');
            }

            Log::info('EMPLOYEE_CONTROLLER: Profile fetched', [
                'user_id'             => $user->id,
                'role'                => $user->role,
                'has_employee_record' => $employee !== null,
            ]);

            return response()->json([
                'user' => [
                    'id'                  => $user->id,
                    'first_name'          => $user->first_name,
                    'last_name'           => $user->last_name,
                    'email'               => $user->email,
                    'role'                => $user->role,
                    'account_status'      => $user->account_status,
                    'current_business_id' => $user->current_business_id,
                ],
                'employee' => $employee ? [
                    'id'                => $employee->id,
                    'employee_id'       => $employee->employee_id,
                    'position'          => $employee->position,
                    'department'        => $employee->department,
                    'status'            => $employee->status,
                    'phone'             => $employee->phone,
                    'national_id'       => $employee->national_id,
                    'address'           => $employee->address,
                    'emergency_contact' => $employee->emergency_contact,
                    'bank_details'      => $employee->bank_details,
                    'profile_pic'       => $employee->profile_pic,
                ] : null,
            ]);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to fetch profile', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to load profile data'], 500);
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        try {
            $validated = $request->validate([
                'first_name'        => 'sometimes|string|max:255',
                'last_name'         => 'sometimes|string|max:255',
                'email'             => 'sometimes|email|unique:users,email,' . $user->id,
                'phone'             => 'nullable|string|max:20',
                'date_of_birth'     => 'nullable|date|before:today',
                'national_id'       => 'nullable|string|max:50',
                'address'           => 'nullable|string|max:500',
                'emergency_contact' => 'nullable|array',
                'bank_details'      => 'nullable|array',
            ]);

            Log::info('EMPLOYEE_CONTROLLER: Updating profile', [
                'user_id' => $user->id,
                'role'    => $user->role,
                'fields'  => array_keys($validated),
            ]);

            DB::beginTransaction();

            $userUpdates = [];
            if (isset($validated['first_name'])) $userUpdates['first_name'] = $validated['first_name'];
            if (isset($validated['last_name']))  $userUpdates['last_name']  = $validated['last_name'];
            if (isset($validated['email']))      $userUpdates['email']      = $validated['email'];
            if (!empty($userUpdates)) $user->update($userUpdates);

            $employee = $user->employee;
            if ($employee) {
                $employeeUpdates = [];
                foreach (['phone', 'date_of_birth', 'national_id', 'address', 'emergency_contact', 'bank_details'] as $field) {
                    if (isset($validated[$field])) $employeeUpdates[$field] = $validated[$field];
                }
                if (!empty($employeeUpdates)) $employee->update($employeeUpdates);
            }

            DB::commit();

            $user->refresh();
            if ($employee) {
                $employee->refresh();
                $employee->load('manager');
            }

            return response()->json([
                'message'  => 'Profile updated successfully',
                'user'     => [
                    'id'                  => $user->id,
                    'first_name'          => $user->first_name,
                    'last_name'           => $user->last_name,
                    'email'               => $user->email,
                    'role'                => $user->role,
                    'current_business_id' => $user->current_business_id,
                ],
                'employee' => $employee ? [
                    'id'                => $employee->id,
                    'employee_id'       => $employee->employee_id,
                    'phone'             => $employee->phone,
                    'national_id'       => $employee->national_id,
                    'address'           => $employee->address,
                    'emergency_contact' => $employee->emergency_contact,
                    'bank_details'      => $employee->bank_details,
                    'profile_pic'       => $employee->profile_pic,
                ] : null,
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            Log::warning('EMPLOYEE_CONTROLLER: Profile update validation failed', [
                'user_id' => $user->id,
                'errors'  => $e->errors(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('EMPLOYEE_CONTROLLER: Failed to update profile', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to update profile.'], 500);
        }
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();
        try {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'password'         => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            ]);

            Log::info('EMPLOYEE_CONTROLLER: Password update requested', [
                'user_id' => $user->id,
                'role'    => $user->role,
            ]);

            if (!Hash::check($validated['current_password'], $user->password)) {
                Log::warning('EMPLOYEE_CONTROLLER: Incorrect current password', ['user_id' => $user->id]);
                throw ValidationException::withMessages([
                    'current_password' => ['The provided password does not match your current password.'],
                ]);
            }

            $user->update(['password' => Hash::make($validated['password'])]);

            Log::info('EMPLOYEE_CONTROLLER: Password updated successfully', ['user_id' => $user->id]);

            return response()->json(['message' => 'Password updated successfully']);
        } catch (ValidationException $e) {
            Log::warning('EMPLOYEE_CONTROLLER: Password update validation failed', [
                'user_id' => $user->id,
                'errors'  => $e->errors(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to update password', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to update password.'], 500);
        }
    }

    public function documents(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;
            if (!$employee) {
                return response()->json(['message' => 'Employee profile not found'], 404);
            }

            $documents = Document::where('employee_id', $employee->id)->get();

            Log::info('EMPLOYEE_CONTROLLER: Documents fetched', [
                'user_id' => $request->user()->id,
                'count'   => $documents->count(),
            ]);

            return response()->json(['data' => $documents]);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to fetch documents', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to fetch documents'], 500);
        }
    }

    public function uploadDocuments(Request $request): JsonResponse
    {
        $user     = $request->user();
        $employee = $user->employee;
        if (!$employee) {
            return response()->json(['message' => 'Employee profile not found'], 404);
        }

        $request->validate([
            'documents'   => 'required|array|max:10',
            'documents.*' => 'file|mimes:pdf,doc,docx,jpg,png|max:5120',
        ]);

        try {
            DB::beginTransaction();
            $newDocuments = [];
            foreach ($request->file('documents') as $file) {
                $path           = $file->store('documents', 'public');
                $newDocuments[] = Document::create([
                    'employee_id' => $employee->id,
                    'file_name'   => $file->getClientOriginalName(),
                    'file_path'   => $path,
                    'type'        => $request->input('type', 'general'),
                    'size'        => $file->getSize(),
                ]);
            }
            DB::commit();

            Log::info('EMPLOYEE_CONTROLLER: Documents uploaded', [
                'user_id' => $user->id,
                'count'   => count($newDocuments),
            ]);

            return response()->json([
                'message'      => 'Documents uploaded successfully',
                'newDocuments' => $newDocuments,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('EMPLOYEE_CONTROLLER: Failed to upload documents', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to upload documents'], 500);
        }
    }

    public function deleteDocument(Request $request, $id): JsonResponse
    {
        try {
            $document = Document::findOrFail($id);
            $employee = $request->user()->employee;

            if ($document->employee_id !== $employee->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
            $document->delete();

            Log::info('EMPLOYEE_CONTROLLER: Document deleted', [
                'document_id' => $id,
                'user_id'     => $request->user()->id,
            ]);

            return response()->json(['message' => 'Document deleted successfully']);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to delete document', [
                'document_id' => $id,
                'error'       => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to delete document'], 500);
        }
    }

    public function downloadDocument(Request $request, $id)
    {
        try {
            $document = Document::findOrFail($id);
            $employee = $request->user()->employee;

            if ($document->employee_id !== $employee->id) abort(403, 'Unauthorized');
            if (!Storage::disk('public')->exists($document->file_path)) abort(404, 'File not found');

            return Storage::disk('public')->download($document->file_path, $document->file_name);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to download document', [
                'document_id' => $id,
                'error'       => $e->getMessage(),
            ]);
            abort(500, 'Failed to download document');
        }
    }

    public function uploadProfilePic(Request $request): JsonResponse
    {
        $user     = $request->user();
        $employee = $user->employee;
        if (!$employee) {
            return response()->json(['message' => 'Employee profile not found'], 404);
        }

        $request->validate(['profile_pic' => 'required|image|mimes:jpeg,png,jpg|max:2048']);

        try {
            $path = $request->file('profile_pic')->store('profile_pics', 'public');
            $employee->update(['profile_pic' => $path]);

            Log::info('EMPLOYEE_CONTROLLER: Profile pic uploaded', ['user_id' => $user->id]);

            return response()->json([
                'message'         => 'Profile picture updated successfully',
                'profile_pic_url' => Storage::url($path),
            ]);
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to upload profile pic', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to upload profile picture'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────

    private function getBusinessScopedEmployees(Request $request)
    {
        $user                = $request->user();
        $requestedBusinessId = $request->query('business_id');
        $query               = Employee::query();

        if ($user->role === 'admin') {
            if ($requestedBusinessId) {
                $businessId = (int) $requestedBusinessId;

                if ($user->current_business_id && $user->current_business_id !== $businessId) {
                    if (!$user->businesses()->where('businesses.id', $businessId)->exists()) {
                        Log::warning('EMPLOYEE_CONTROLLER: Admin attempting to access unauthorized business', [
                            'admin_id'               => $user->id,
                            'admin_business_id'      => $user->current_business_id,
                            'requested_business_id'  => $businessId,
                        ]);
                        $query->where('business_id', 0);
                        return $query;
                    }
                }

                $query->where('business_id', $businessId);

                Log::info('EMPLOYEE_CONTROLLER: Admin filtering by requested business', [
                    'admin_id'    => $user->id,
                    'business_id' => $businessId,
                ]);
            } elseif ($user->current_business_id) {
                $query->where('business_id', $user->current_business_id);

                Log::info('EMPLOYEE_CONTROLLER: Admin filtering by current business', [
                    'admin_id'    => $user->id,
                    'business_id' => $user->current_business_id,
                ]);
            } elseif ($user->businesses()->exists()) {
                $businessIds = $user->businesses()->pluck('businesses.id');
                $query->whereIn('business_id', $businessIds);

                Log::info('EMPLOYEE_CONTROLLER: Admin filtering by managed businesses', [
                    'admin_id'     => $user->id,
                    'business_ids' => $businessIds,
                ]);
            } else {
                Log::info('EMPLOYEE_CONTROLLER: Super admin - showing all employees', ['admin_id' => $user->id]);
            }
        } elseif ($user->role === 'manager') {
            $managerEmployee = Employee::where('user_id', $user->id)->first();

            if ($managerEmployee && $managerEmployee->business_id) {
                $query->where('business_id', $managerEmployee->business_id)
                      ->where('manager_id', $user->id);

                Log::info('EMPLOYEE_CONTROLLER: Manager filtering by business and team', [
                    'manager_id'  => $user->id,
                    'business_id' => $managerEmployee->business_id,
                ]);
            } elseif ($managerEmployee) {
                $query->whereNull('business_id')->where('manager_id', $user->id);

                Log::info('EMPLOYEE_CONTROLLER: Manager without business filtering team', ['manager_id' => $user->id]);
            } else {
                $query->where('id', 0);

                Log::info('EMPLOYEE_CONTROLLER: Manager has no employee record', ['manager_id' => $user->id]);
            }
        } elseif ($user->role === 'employee') {
            $query->where('user_id', $user->id);

            Log::info('EMPLOYEE_CONTROLLER: Employee viewing own record', ['employee_user_id' => $user->id]);
        }

        return $query;
    }

    private function transformEmployees($employees, bool $includeStatusMeta = false)
    {
        return $employees->map(function ($employee) use ($includeStatusMeta) {
            $payload = $this->buildEmployeePayload($employee);

            if ($includeStatusMeta) {
                $payload['suspended_at']      = $employee->suspended_at;
                $payload['archived_at']       = $employee->archived_at;
                $payload['reinstated_at']     = $employee->reinstated_at;
                $payload['suspension_reason'] = $employee->suspension_reason;
                $payload['archive_reason']    = $employee->archive_reason;
                $payload['status_notes']      = $employee->status_notes;
                $payload['suspended_by_name'] = $employee->suspendedByUser
                    ? $employee->suspendedByUser->first_name . ' ' . $employee->suspendedByUser->last_name
                    : null;
                $payload['archived_by_name']  = $employee->archivedByUser
                    ? $employee->archivedByUser->first_name . ' ' . $employee->archivedByUser->last_name
                    : null;
            }

            return $payload;
        });
    }

    private function buildEmployeePayload(Employee $employee): array
    {
        return [
            'id'                  => $employee->id,
            'employee_id'         => $employee->employee_id,
            'user_id'             => $employee->user_id,
            'business_id'         => $employee->business_id,
            'country_id'          => $employee->country_id,
            'manager_id'          => $employee->manager_id,
            'first_name'          => $employee->user->first_name  ?? $employee->first_name,
            'last_name'           => $employee->user->last_name   ?? $employee->last_name,
            'email'               => $employee->user->email       ?? $employee->email,
            'role'                => $employee->user->role        ?? 'employee',
            'position'            => $employee->position,
            'department'          => $employee->department,
            'base_salary'         => (float) $employee->base_salary,
            'transport_allowance' => (float) $employee->transport_allowance,
            'lunch_allowance'     => (float) $employee->lunch_allowance,
            'employment_type'     => $employee->employment_type,
            'hire_date'           => $employee->hire_date,
            'status'              => $employee->status,
            'is_active'           => $employee->isActive(),
            'created_at'          => $employee->created_at,
            'updated_at'          => $employee->updated_at,
            'phone'               => $employee->phone,
            'national_id'         => $employee->national_id,
            'address'             => $employee->address,
            'emergency_contact'   => $employee->emergency_contact,
            'bank_details'        => $employee->bank_details,
            'business' => $employee->business ? [
                'id'       => $employee->business->id,
                'name'     => $employee->business->name,
                'industry' => $employee->business->industry,
            ] : null,
            'country' => $employee->country ? [
                'id'              => $employee->country->id,
                'code'            => $employee->country->code,
                'name'            => $employee->country->name,
                'currency_code'   => $employee->country->currency_code,
                'currency_symbol' => $employee->country->currency_symbol,
                'flag'            => $employee->country->flag,
                'is_active'       => $employee->country->is_active,
            ] : null,
            'manager' => $employee->manager ? [
                'id'         => $employee->manager->id,
                'first_name' => $employee->manager->first_name,
                'last_name'  => $employee->manager->last_name,
                'email'      => $employee->manager->email,
            ] : null,
            'user' => $employee->user ? [
                'id'             => $employee->user->id,
                'first_name'     => $employee->user->first_name,
                'last_name'      => $employee->user->last_name,
                'email'          => $employee->user->email,
                'role'           => $employee->user->role,
                'account_status' => $employee->user->account_status,
            ] : null,
        ];
    }

    private function getDefaultPassword(): string
    {
        try {
            $setting  = SystemSetting::where('key', 'default_password')->first();
            $password = trim($setting->value ?? '');

            if (strlen($password) < 8) {
                Log::warning('EMPLOYEE_CONTROLLER: Default password too short or missing, using fallback');
                return 'Password123!';
            }

            return $password;
        } catch (\Exception $e) {
            Log::error('EMPLOYEE_CONTROLLER: Failed to get default password', ['error' => $e->getMessage()]);
            return 'Password123!';
        }
    }
}