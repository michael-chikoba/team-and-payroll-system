<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminPermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminPermissionController extends Controller
{
    /**
     * List all admins for the actor's business, with their permission records.
     *
     * GET /api/admin/admin-permissions
     * Optional: ?business_id=2
     */
    public function index(Request $request): JsonResponse
    {
        $actor = Auth::user();
        
        if (!$actor) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        $businessId = $this->resolveBusinessId($request, $actor);

        $this->authorizeManage($actor, $businessId);

        /*
         * Join chain:
         *   business_admins  →  users (for email)
         *                    →  employees (for first_name/last_name, profile_pic,
         *                                  scoped to the same business)
         *                    →  admin_permissions (optional — may not exist yet)
         *
         * employees is LEFT-joined so admins who have no employee record
         * (e.g. the original super-owner) still appear.
         */
        $admins = DB::table('business_admins as ba')
            ->join('users as u', 'u.id', '=', 'ba.user_id')
            ->leftJoin('employees as e', function ($join) use ($businessId) {
                $join->on('e.user_id', '=', 'ba.user_id')
                     ->where('e.business_id', '=', $businessId);
            })
            ->leftJoin('admin_permissions as ap', function ($join) use ($businessId) {
                $join->on('ap.user_id', '=', 'ba.user_id')
                     ->where('ap.business_id', '=', $businessId);
            })
            ->where('ba.business_id', $businessId)
            ->select([
                'ba.id as business_admin_id',
                'ba.user_id',
                'ba.role',
                'ba.is_primary',
                'u.email',
                // users has first_name + last_name (no single name column)
                // profile_pic comes from employees joined by user_id + business_id
                DB::raw("COALESCE(
                    NULLIF(TRIM(CONCAT(
                        COALESCE(u.first_name, ''),
                        ' ',
                        COALESCE(u.last_name, '')
                    )), ''),
                    SUBSTRING_INDEX(u.email, '@', 1)
                ) as name"),
                'e.profile_pic as profile_photo_path',
                'e.position',
                'e.department',
                'ba.created_at as member_since',
                // Permission flags — default to 0 when no row exists yet
                'ap.id as permission_id',
                DB::raw('COALESCE(ap.cannot_add_employee,  0) as cannot_add_employee'),
                DB::raw('COALESCE(ap.cannot_view_payroll,  0) as cannot_view_payroll'),
                DB::raw('COALESCE(ap.cannot_view_payslip,  0) as cannot_view_payslip'),
                DB::raw('COALESCE(ap.cannot_manage_admins, 0) as cannot_manage_admins'),
                DB::raw('COALESCE(ap.is_suspended,         0) as is_suspended'),
                'ap.suspension_reason',
                'ap.suspended_at',
            ])
            ->orderByRaw("FIELD(ba.role, 'owner', 'admin', 'manager')")  // owner → admin → manager
            ->orderByRaw("COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))), ''), u.email) ASC")
            ->get()
            ->map(function ($row) {
                // Cast tinyint COALESCE results to proper booleans
                $row->cannot_add_employee  = (bool) $row->cannot_add_employee;
                $row->cannot_view_payroll  = (bool) $row->cannot_view_payroll;
                $row->cannot_view_payslip  = (bool) $row->cannot_view_payslip;
                $row->cannot_manage_admins = (bool) $row->cannot_manage_admins;
                $row->is_suspended         = (bool) $row->is_suspended;
                return $row;
            });

        return response()->json([
            'data'        => $admins,
            'business_id' => $businessId,
        ]);
    }

    /**
     * Update (or create) restriction flags for a specific admin.
     *
     * PUT /api/admin/admin-permissions/{userId}
     * Body: { business_id?, cannot_add_employee?, cannot_view_payroll?,
     *         cannot_view_payslip?, cannot_manage_admins? }
     */
    public function update(Request $request, int $userId): JsonResponse
    {
        $actor = Auth::user();
        
        if (!$actor) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        $businessId = $this->resolveBusinessId($request, $actor);

        $this->authorizeManage($actor, $businessId);

        if ($actor->id === $userId) {
            return response()->json(['message' => 'You cannot modify your own permissions.'], 403);
        }

        // Ensure target is actually an admin of this business
        $targetRecord = DB::table('business_admins')
            ->where('user_id', $userId)
            ->where('business_id', $businessId)
            ->first();

        if (!$targetRecord) {
            return response()->json(['message' => 'User is not an admin of this business.'], 404);
        }

        $validated = $request->validate([
            'cannot_add_employee'  => 'sometimes|boolean',
            'cannot_view_payroll'  => 'sometimes|boolean',
            'cannot_view_payslip'  => 'sometimes|boolean',
            'cannot_manage_admins' => 'sometimes|boolean',
        ]);

        if (empty($validated)) {
            return response()->json(['message' => 'No permission flags provided.'], 422);
        }

        $permission = AdminPermission::updateOrCreate(
            ['user_id' => $userId, 'business_id' => $businessId],
            $validated
        );

        Log::info('Admin permissions updated', [
            'actor_id'    => $actor->id,
            'target_user' => $userId,
            'business_id' => $businessId,
            'changes'     => $validated,
        ]);

        return response()->json([
            'message'     => 'Permissions updated successfully.',
            'permissions' => [
                'cannot_add_employee'  => (bool) $permission->cannot_add_employee,
                'cannot_view_payroll'  => (bool) $permission->cannot_view_payroll,
                'cannot_view_payslip'  => (bool) $permission->cannot_view_payslip,
                'cannot_manage_admins' => (bool) $permission->cannot_manage_admins,
                'is_suspended'         => (bool) $permission->is_suspended,
            ],
        ]);
    }

    /**
     * Change an admin's role within a business.
     *
     * PATCH /api/admin/admin-permissions/{userId}/role
     * Body: { business_id?, role: 'owner'|'admin'|'manager' }
     */
    public function updateRole(Request $request, int $userId): JsonResponse
    {
        $actor = Auth::user();
        
        if (!$actor) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        $businessId = $this->resolveBusinessId($request, $actor);

        $this->authorizeManage($actor, $businessId);

        if ($actor->id === $userId) {
            return response()->json(['message' => 'You cannot change your own role.'], 403);
        }

        $validated = $request->validate([
            'role' => 'required|in:owner,admin,manager',
        ]);

        $updated = DB::table('business_admins')
            ->where('user_id', $userId)
            ->where('business_id', $businessId)
            ->update(['role' => $validated['role'], 'updated_at' => now()]);

        if (!$updated) {
            return response()->json(['message' => 'Admin record not found for this business.'], 404);
        }

        return response()->json(['message' => 'Role updated to "' . $validated['role'] . '".']);
    }

    /**
     * Suspend an admin for this business.
     *
     * POST /api/admin/admin-permissions/{userId}/suspend
     * Body: { business_id?, reason? }
     */
    public function suspend(Request $request, int $userId): JsonResponse
    {
        $actor = Auth::user();
        
        if (!$actor) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        $businessId = $this->resolveBusinessId($request, $actor);

        $this->authorizeManage($actor, $businessId);

        if ($actor->id === $userId) {
            return response()->json(['message' => 'You cannot suspend yourself.'], 403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        AdminPermission::updateOrCreate(
            ['user_id' => $userId, 'business_id' => $businessId],
            [
                'is_suspended'      => true,
                'suspension_reason' => $validated['reason'] ?? null,
                'suspended_at'      => now(),
                'suspended_by'      => $actor->id,
            ]
        );

        return response()->json(['message' => 'Admin suspended successfully.']);
    }

    /**
     * Lift suspension for an admin.
     *
     * POST /api/admin/admin-permissions/{userId}/unsuspend
     * Body: { business_id? }
     */
    public function unsuspend(Request $request, int $userId): JsonResponse
    {
        $actor = Auth::user();
        
        if (!$actor) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        $businessId = $this->resolveBusinessId($request, $actor);

        $this->authorizeManage($actor, $businessId);

        AdminPermission::updateOrCreate(
            ['user_id' => $userId, 'business_id' => $businessId],
            [
                'is_suspended'      => false,
                'suspension_reason' => null,
                'suspended_at'      => null,
                'suspended_by'      => null,
            ]
        );

        return response()->json(['message' => 'Admin suspension lifted.']);
    }

   /**
 * Return the logged-in admin's OWN restriction flags.
 * Called on every page load by the frontend composable.
 *
 * GET /api/admin/my-permissions
 * Optional: ?business_id=2
 */
public function myPermissions(Request $request): JsonResponse
{
    // Log the request for debugging
    Log::info('myPermissions called', [
        'headers' => $request->headers->all(),
        'business_id_param' => $request->input('business_id') ?? $request->query('business_id'),
        'url' => $request->fullUrl()
    ]);

    // Try to authenticate via token first
    $user = null;
    
    // Check for Bearer token in Authorization header
    $authHeader = $request->header('Authorization');
    if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
        $token = substr($authHeader, 7);
        
        // Find the token in personal_access_tokens table
        $accessToken = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $token))
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first();
            
        if ($accessToken) {
            $user = User::find($accessToken->tokenable_id);
            Log::info('myPermissions - Authenticated via token', [
                'user_id' => $user?->id,
                'email' => $user?->email
            ]);
        }
    }
    
    // Fall back to session auth if token auth failed
    if (!$user) {
        $user = Auth::user();
        if ($user) {
            Log::info('myPermissions - Authenticated via session', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        }
    }
    
    if (!$user) {
        Log::warning('myPermissions: No authenticated user', [
            'session_id' => session()->getId(),
            'auth_check' => Auth::check(),
            'has_bearer' => !is_null($authHeader)
        ]);
        return response()->json($this->defaultFlags());
    }

    Log::info('myPermissions - User authenticated:', [
        'id' => $user->id,
        'email' => $user->email,
        'name' => $user->name
    ]);

    $businessId = $this->resolveBusinessId($request, $user);

    Log::info('myPermissions - Resolved business:', [
        'business_id' => $businessId,
        'from_request' => $request->input('business_id') ?? $request->query('business_id')
    ]);

    if (!$businessId) {
        Log::warning('myPermissions: No business ID resolved');
        return response()->json($this->defaultFlags());
    }

    // Check if user is an admin of this business
    $isAdmin = DB::table('business_admins')
        ->where('user_id', $user->id)
        ->where('business_id', $businessId)
        ->exists();

    Log::info('myPermissions - Admin check:', [
        'is_admin' => $isAdmin
    ]);

    if (!$isAdmin) {
        Log::warning('myPermissions: User is not an admin', [
            'user_id' => $user->id,
            'business_id' => $businessId
        ]);
        return response()->json($this->defaultFlags());
    }

    // Get permissions from database
    $perms = AdminPermission::where('user_id', $user->id)
        ->where('business_id', $businessId)
        ->first();

    Log::info('myPermissions - Permissions from DB:', [
        'has_permissions' => !is_null($perms),
        'permissions' => $perms ? $perms->toArray() : null
    ]);

    // If no permissions record exists, create one with default values (all false)
    if (!$perms) {
        Log::info('myPermissions - Creating default permissions record');
        $perms = AdminPermission::create([
            'user_id' => $user->id,
            'business_id' => $businessId,
            'cannot_add_employee' => false,
            'cannot_view_payroll' => false,
            'cannot_view_payslip' => false,
            'cannot_manage_admins' => false,
            'is_suspended' => false,
        ]);
    }

    $response = [
        'cannot_add_employee'  => (bool) $perms->cannot_add_employee,
        'cannot_view_payroll'  => (bool) $perms->cannot_view_payroll,
        'cannot_view_payslip'  => (bool) $perms->cannot_view_payslip,
        'cannot_manage_admins' => (bool) $perms->cannot_manage_admins,
        'is_suspended'         => (bool) $perms->is_suspended,
    ];

    Log::info('myPermissions - Sending response:', $response);

    return response()->json($response);
}

    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * Resolve which business to operate on.
     *
     * Priority:
     *   1. Explicit business_id in request body or query string
     *   2. First business the actor is linked to in business_admins
     *      (owner records preferred over admin/manager)
     */
    private function resolveBusinessId(Request $request, ?User $actor): ?int
    {
        // First check for explicit business_id in request
        $explicit = $request->input('business_id') ?? $request->query('business_id');
        if ($explicit) {
            return (int) $explicit;
        }

        // If no actor, can't resolve from database
        if (!$actor) {
            return null;
        }

        // Try to get from user's business associations
        $record = DB::table('business_admins')
            ->where('user_id', $actor->id)
            ->orderByRaw("FIELD(role, 'owner', 'admin', 'manager')")
            ->first();

        return $record?->business_id ?? null;
    }

    /**
     * Verify the actor may manage admins for the given business.
     * Superadmins always pass. Otherwise must be owner or admin in business_admins.
     */
    private function authorizeManage(?User $actor, ?int $businessId): void
    {
        if (!$actor) {
            abort(401, 'Unauthenticated.');
        }

        if (!$businessId) {
            abort(403, 'No business found for your account.');
        }

        if (!empty($actor->is_superadmin)) {
            return;
        }

        $record = DB::table('business_admins')
            ->where('user_id', $actor->id)
            ->where('business_id', $businessId)
            ->first();

        if (!$record) {
            abort(403, 'You are not an admin of this business.');
        }

        if (!in_array($record->role, ['owner', 'admin'])) {
            abort(403, 'Only business owners and admins can manage permissions.');
        }
    }

    private function defaultFlags(): array
    {
        return [
            'cannot_add_employee'  => false,
            'cannot_view_payroll'  => false,
            'cannot_view_payslip'  => false,
            'cannot_manage_admins' => false,
            'is_suspended'         => false,
        ];
    }
}