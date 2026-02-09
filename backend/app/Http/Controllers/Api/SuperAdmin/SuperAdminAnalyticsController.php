<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use App\Models\Employee;
use App\Models\BusinessActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminAnalyticsController extends Controller
{
    /**
     * Get system overview analytics
     */
    public function overview(Request $request): JsonResponse
    {
        try {
            $stats = [
                'businesses' => [
                    'total' => Business::count(),
                    'active' => Business::active()->count(),
                    'suspended' => Business::suspended()->count(),
                    'trial' => Business::onTrial()->count(),
                    'verified' => Business::verified()->count(),
                ],
                'subscriptions' => [
                    'active' => Business::withActiveSubscription()->count(),
                    'expired' => Business::whereDoesntHave('businesses', function ($q) {
                        $q->withActiveSubscription();
                    })->count(),
                    'by_tier' => Business::select('subscription_tier', DB::raw('count(*) as count'))
                        ->groupBy('subscription_tier')
                        ->get()
                        ->pluck('count', 'subscription_tier'),
                ],
                'users' => [
                    'total' => User::count(),
                    'superadmins' => User::where('is_superadmin', true)->count(),
                    'admins' => User::where('role', 'admin')->count(),
                    'managers' => User::where('role', 'manager')->count(),
                    'employees' => User::where('role', 'employee')->count(),
                ],
                'employees' => [
                    'total' => Business::sum('current_employee_count'),
                    'total_limit' => Business::sum('employee_limit'),
                    'average_usage' => round(Business::avg('current_employee_count'), 2),
                    'businesses_at_limit' => Business::atLimit()->count(),
                ],
            ];

            // Growth trends (last 30 days)
            $growthData = $this->getGrowthTrends();

            // Recent activity
            $recentActivity = BusinessActivityLog::with(['business', 'admin'])
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'growth' => $growthData,
                    'recent_activity' => $recentActivity,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('SuperAdmin: Failed to fetch overview analytics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch analytics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get revenue analytics (can be customized based on your billing system)
     */
    public function revenue(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'month'); // day, week, month, year

            // Define tier pricing (customize as needed)
            $tierPricing = [
                'basic' => 29,
                'standard' => 79,
                'premium' => 199,
                'enterprise' => 499,
            ];

            // Calculate MRR (Monthly Recurring Revenue)
            $mrr = Business::where('is_trial', false)
                ->withActiveSubscription()
                ->get()
                ->sum(function ($business) use ($tierPricing) {
                    return $tierPricing[$business->subscription_tier] ?? 0;
                });

            // Calculate ARR (Annual Recurring Revenue)
            $arr = $mrr * 12;

            // Revenue by tier
            $revenueByTier = Business::where('is_trial', false)
                ->withActiveSubscription()
                ->select('subscription_tier', DB::raw('count(*) as count'))
                ->groupBy('subscription_tier')
                ->get()
                ->mapWithKeys(function ($item) use ($tierPricing) {
                    $price = $tierPricing[$item->subscription_tier] ?? 0;
                    return [
                        $item->subscription_tier => [
                            'count' => $item->count,
                            'mrr' => $price * $item->count,
                            'arr' => $price * $item->count * 12,
                        ]
                    ];
                });

            // Trial conversions
            $totalTrials = Business::where('is_trial', true)->count();
            $convertedTrials = Business::where('is_trial', false)
                ->whereNotNull('subscription_start_date')
                ->count();
            $conversionRate = $totalTrials > 0 
                ? round(($convertedTrials / $totalTrials) * 100, 2) 
                : 0;

            // Churn rate (simplified - businesses that expired in last 30 days)
            $expiredLast30Days = Business::where('subscription_end_date', '<', now())
                ->where('subscription_end_date', '>=', now()->subDays(30))
                ->count();
            $activeStart30Days = Business::withActiveSubscription()->count() + $expiredLast30Days;
            $churnRate = $activeStart30Days > 0 
                ? round(($expiredLast30Days / $activeStart30Days) * 100, 2) 
                : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'mrr' => $mrr,
                    'arr' => $arr,
                    'revenue_by_tier' => $revenueByTier,
                    'trial_conversion_rate' => $conversionRate,
                    'churn_rate' => $churnRate,
                    'active_subscriptions' => Business::withActiveSubscription()->count(),
                    'trial_accounts' => $totalTrials,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('SuperAdmin: Failed to fetch revenue analytics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch revenue analytics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get growth analytics
     */
    public function growth(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 30); // days

            $data = $this->getGrowthTrends($period);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            \Log::error('SuperAdmin: Failed to fetch growth analytics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch growth analytics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get usage analytics
     */
    public function usage(Request $request): JsonResponse
    {
        try {
            // Employee usage distribution
            $usageDistribution = Business::selectRaw('
                CASE 
                    WHEN (current_employee_count / employee_limit * 100) < 50 THEN "0-50%"
                    WHEN (current_employee_count / employee_limit * 100) < 75 THEN "50-75%"
                    WHEN (current_employee_count / employee_limit * 100) < 90 THEN "75-90%"
                    ELSE "90-100%"
                END as usage_range,
                COUNT(*) as count
            ')
            ->groupBy('usage_range')
            ->get()
            ->pluck('count', 'usage_range');

            // Top businesses by employee count
            $topBusinesses = Business::orderBy('current_employee_count', 'desc')
                ->limit(10)
                ->get(['id', 'name', 'current_employee_count', 'employee_limit', 'subscription_tier']);

            // Businesses needing attention
            $needsAttention = Business::with(['country', 'admins'])
                ->where(function ($q) {
                    $q->whereColumn('current_employee_count', '>=', 'employee_limit')
                        ->orWhere('subscription_end_date', '<=', now()->addDays(7))
                        ->orWhereNotNull('suspended_at');
                })
                ->get()
                ->map(function ($business) {
                    return [
                        'id' => $business->id,
                        'name' => $business->name,
                        'issues' => $business->needsAttention(),
                        'subscription_tier' => $business->subscription_tier,
                        'employee_usage' => $business->employee_usage_percentage,
                    ];
                });

            // Feature usage
            $featureUsage = Business::whereNotNull('features')
                ->get()
                ->flatMap(function ($business) {
                    return $business->features ?? [];
                })
                ->countBy()
                ->sortDesc();

            return response()->json([
                'success' => true,
                'data' => [
                    'usage_distribution' => $usageDistribution,
                    'top_businesses' => $topBusinesses,
                    'needs_attention' => $needsAttention,
                    'feature_usage' => $featureUsage,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('SuperAdmin: Failed to fetch usage analytics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch usage analytics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Helper: Get growth trends
     */
    private function getGrowthTrends(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        // Businesses created per day
        $businessGrowth = Business::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        // Users created per day
        $userGrowth = User::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        // Active subscriptions over time
        $subscriptionGrowth = [];
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->toDateString();
            $count = Business::where('subscription_start_date', '<=', $date)
                ->where(function ($q) use ($date) {
                    $q->where('subscription_end_date', '>=', $date)
                        ->orWhere('trial_end_date', '>=', $date);
                })
                ->count();
            $subscriptionGrowth[$date] = $count;
        }

        return [
            'business_growth' => $businessGrowth,
            'user_growth' => $userGrowth,
            'subscription_growth' => array_reverse($subscriptionGrowth, true),
        ];
    }
}