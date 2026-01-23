<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginAudit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AuditController extends Controller
{
    /**
     * Get login audit logs
     * GET /api/admin/audit/logins
     */
    public function loginAudits(Request $request): JsonResponse
    {
        $query = LoginAudit::with('user:id,first_name,last_name,email,role')
            ->orderBy('login_at', 'desc');

        // Filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('country_code')) {
            $query->where('country_code', $request->country_code);
        }

        if ($request->has('start_date')) {
            $query->where('login_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('login_at', '<=', $request->end_date);
        }

        if ($request->has('ip')) {
            $query->where('ip_address', 'like', "%{$request->ip}%");
        }

        if ($request->has('device_type')) {
            $query->where('device_type', $request->device_type);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 50);
        $audits = $query->paginate($perPage);

        // Add computed fields
        $audits->getCollection()->transform(function ($audit) {
            return [
                'id' => $audit->id,
                'user' => $audit->user ? [
                    'id' => $audit->user->id,
                    'name' => trim($audit->user->first_name . ' ' . $audit->user->last_name),
                    'email' => $audit->user->email,
                    'role' => $audit->user->role,
                ] : null,
                'email' => $audit->email,
                'login_at' => $audit->login_at,
                'logout_at' => $audit->logout_at,
                'ip_address' => $audit->ip_address,
                'location' => $audit->location,
                'city' => $audit->city,
                'region' => $audit->region,
                'country' => $audit->country,
                'country_code' => $audit->country_code,
                'latitude' => $audit->latitude,
                'longitude' => $audit->longitude,
                'device_type' => $audit->device_type,
                'browser' => $audit->browser,
                'platform' => $audit->platform,
                'isp' => $audit->isp,
                'status' => $audit->status,
                'failure_reason' => $audit->failure_reason,
                'session_duration_minutes' => $audit->session_duration,
                'is_suspicious' => $audit->isSuspicious(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $audits->items(),
            'pagination' => [
                'current_page' => $audits->currentPage(),
                'per_page' => $audits->perPage(),
                'total' => $audits->total(),
                'last_page' => $audits->lastPage(),
            ]
        ]);
    }

    /**
     * Get login statistics
     * GET /api/admin/audit/login-stats
     */
    public function loginStats(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', now()->subDays(30));
        $endDate = $request->get('end_date', now());

        $stats = [
            'total_logins' => LoginAudit::whereBetween('login_at', [$startDate, $endDate])
                ->where('status', 'success')
                ->count(),
            
            'failed_logins' => LoginAudit::whereBetween('login_at', [$startDate, $endDate])
                ->where('status', 'failed')
                ->count(),
            
            'unique_users' => LoginAudit::whereBetween('login_at', [$startDate, $endDate])
                ->where('status', 'success')
                ->distinct('user_id')
                ->count('user_id'),
            
            'unique_ips' => LoginAudit::whereBetween('login_at', [$startDate, $endDate])
                ->distinct('ip_address')
                ->count('ip_address'),
            
            'logins_by_country' => LoginAudit::whereBetween('login_at', [$startDate, $endDate])
                ->where('status', 'success')
                ->whereNotNull('country')
                ->selectRaw('country, country_code, COUNT(*) as count')
                ->groupBy('country', 'country_code')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
            
            'logins_by_device' => LoginAudit::whereBetween('login_at', [$startDate, $endDate])
                ->where('status', 'success')
                ->selectRaw('device_type, COUNT(*) as count')
                ->groupBy('device_type')
                ->get(),
            
            'logins_by_browser' => LoginAudit::whereBetween('login_at', [$startDate, $endDate])
                ->where('status', 'success')
                ->selectRaw('browser, COUNT(*) as count')
                ->groupBy('browser')
                ->orderByDesc('count')
                ->get(),
            
            'logins_by_hour' => LoginAudit::whereBetween('login_at', [$startDate, $endDate])
                ->where('status', 'success')
                ->selectRaw('HOUR(login_at) as hour, COUNT(*) as count')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get(),
            
            'suspicious_logins' => LoginAudit::whereBetween('login_at', [$startDate, $endDate])
                ->where('status', 'success')
                ->get()
                ->filter(fn($audit) => $audit->isSuspicious())
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ]
        ]);
    }

    /**
     * Get user's login history
     * GET /api/admin/audit/user/{userId}/logins
     */
    public function userLoginHistory(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $logins = LoginAudit::where('user_id', $userId)
            ->orderBy('login_at', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($audit) {
                return [
                    'id' => $audit->id,
                    'login_at' => $audit->login_at,
                    'logout_at' => $audit->logout_at,
                    'ip_address' => $audit->ip_address,
                    'location' => $audit->location,
                    'device_type' => $audit->device_type,
                    'browser' => $audit->browser,
                    'platform' => $audit->platform,
                    'status' => $audit->status,
                    'failure_reason' => $audit->failure_reason,
                    'session_duration' => $audit->session_duration,
                    'is_suspicious' => $audit->isSuspicious(),
                ];
            });

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
            ],
            'logins' => $logins
        ]);
    }
}