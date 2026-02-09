<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class SuperAdminSettingsController extends Controller
{
    /**
     * Get subscription tier configurations
     */
    public function getSubscriptionTiers(): JsonResponse
    {
        try {
            $tiers = Cache::remember('subscription_tiers', 3600, function () {
                return [
                    'basic' => [
                        'name' => 'Basic',
                        'description' => 'Perfect for small teams getting started',
                        'price' => 29,
                        'employee_limit' => 50,
                        'features' => [
                            'attendance_tracking',
                            'basic_reporting',
                            'employee_management',
                            'leave_management',
                        ],
                        'restrictions' => [],
                    ],
                    'standard' => [
                        'name' => 'Standard',
                        'description' => 'Great for growing businesses',
                        'price' => 79,
                        'employee_limit' => 150,
                        'features' => [
                            'attendance_tracking',
                            'basic_reporting',
                            'advanced_reporting',
                            'employee_management',
                            'leave_management',
                            'payroll_management',
                            'shift_scheduling',
                        ],
                        'restrictions' => [],
                    ],
                    'premium' => [
                        'name' => 'Premium',
                        'description' => 'Advanced features for established companies',
                        'price' => 199,
                        'employee_limit' => 500,
                        'features' => [
                            'attendance_tracking',
                            'basic_reporting',
                            'advanced_reporting',
                            'employee_management',
                            'leave_management',
                            'payroll_management',
                            'shift_scheduling',
                            'custom_workflows',
                            'api_access',
                            'integrations',
                            'priority_support',
                        ],
                        'restrictions' => [],
                    ],
                    'enterprise' => [
                        'name' => 'Enterprise',
                        'description' => 'Custom solutions for large organizations',
                        'price' => 499,
                        'employee_limit' => 10000,
                        'features' => [
                            'attendance_tracking',
                            'basic_reporting',
                            'advanced_reporting',
                            'employee_management',
                            'leave_management',
                            'payroll_management',
                            'shift_scheduling',
                            'custom_workflows',
                            'api_access',
                            'integrations',
                            'priority_support',
                            'white_label',
                            'dedicated_support',
                            'custom_development',
                            'sla_guarantee',
                        ],
                        'restrictions' => [],
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $tiers
            ]);

        } catch (\Exception $e) {
            \Log::error('SuperAdmin: Failed to fetch subscription tiers: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subscription tiers',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update subscription tier configurations
     */
    public function updateSubscriptionTiers(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tiers' => 'required|array',
            'tiers.*.name' => 'required|string',
            'tiers.*.price' => 'required|numeric|min:0',
            'tiers.*.employee_limit' => 'required|integer|min:1',
            'tiers.*.features' => 'required|array',
            'tiers.*.restrictions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Store in cache
            Cache::put('subscription_tiers', $request->tiers, 3600);

            // Optionally, store in database or config file
            // config(['subscriptions.tiers' => $request->tiers]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription tiers updated successfully',
                'data' => $request->tiers
            ]);

        } catch (\Exception $e) {
            \Log::error('SuperAdmin: Failed to update subscription tiers: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subscription tiers',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get available features
     */
    public function getFeatures(): JsonResponse
    {
        try {
            $features = [
                'attendance_tracking' => [
                    'name' => 'Attendance Tracking',
                    'description' => 'Track employee clock in/out times',
                    'category' => 'core',
                ],
                'basic_reporting' => [
                    'name' => 'Basic Reporting',
                    'description' => 'Generate basic attendance and leave reports',
                    'category' => 'reporting',
                ],
                'advanced_reporting' => [
                    'name' => 'Advanced Reporting',
                    'description' => 'Advanced analytics and custom reports',
                    'category' => 'reporting',
                ],
                'employee_management' => [
                    'name' => 'Employee Management',
                    'description' => 'Manage employee profiles and documents',
                    'category' => 'core',
                ],
                'leave_management' => [
                    'name' => 'Leave Management',
                    'description' => 'Handle leave requests and approvals',
                    'category' => 'core',
                ],
                'payroll_management' => [
                    'name' => 'Payroll Management',
                    'description' => 'Process payroll and generate payslips',
                    'category' => 'finance',
                ],
                'shift_scheduling' => [
                    'name' => 'Shift Scheduling',
                    'description' => 'Create and manage employee shifts',
                    'category' => 'scheduling',
                ],
                'custom_workflows' => [
                    'name' => 'Custom Workflows',
                    'description' => 'Create custom approval workflows',
                    'category' => 'advanced',
                ],
                'api_access' => [
                    'name' => 'API Access',
                    'description' => 'Access to REST API for integrations',
                    'category' => 'integration',
                ],
                'integrations' => [
                    'name' => 'Third-party Integrations',
                    'description' => 'Connect with external services',
                    'category' => 'integration',
                ],
                'priority_support' => [
                    'name' => 'Priority Support',
                    'description' => '24/7 priority customer support',
                    'category' => 'support',
                ],
                'white_label' => [
                    'name' => 'White Label',
                    'description' => 'Custom branding and white-label option',
                    'category' => 'enterprise',
                ],
                'dedicated_support' => [
                    'name' => 'Dedicated Support',
                    'description' => 'Dedicated account manager',
                    'category' => 'support',
                ],
                'custom_development' => [
                    'name' => 'Custom Development',
                    'description' => 'Custom feature development',
                    'category' => 'enterprise',
                ],
                'sla_guarantee' => [
                    'name' => 'SLA Guarantee',
                    'description' => '99.9% uptime guarantee',
                    'category' => 'enterprise',
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $features
            ]);

        } catch (\Exception $e) {
            \Log::error('SuperAdmin: Failed to fetch features: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch features',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update available features
     */
    public function updateFeatures(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'features' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Store in cache or database
            Cache::put('available_features', $request->features, 3600);

            return response()->json([
                'success' => true,
                'message' => 'Features updated successfully',
                'data' => $request->features
            ]);

        } catch (\Exception $e) {
            \Log::error('SuperAdmin: Failed to update features: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update features',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get system settings
     */
    public function getSystemSettings(): JsonResponse
    {
        try {
            $settings = [
                'trial_period_days' => config('app.trial_period_days', 30),
                'default_employee_limit' => config('app.default_employee_limit', 50),
                'default_subscription_tier' => config('app.default_subscription_tier', 'basic'),
                'enable_trial_auto_conversion' => config('app.enable_trial_auto_conversion', false),
                'enable_email_notifications' => config('app.enable_email_notifications', true),
                'enable_auto_suspend_expired' => config('app.enable_auto_suspend_expired', false),
                'days_before_expiry_warning' => config('app.days_before_expiry_warning', 7),
            ];

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            \Log::error('SuperAdmin: Failed to fetch system settings: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch system settings',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update system settings
     */
    public function updateSystemSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'trial_period_days' => 'sometimes|integer|min:1|max:365',
            'default_employee_limit' => 'sometimes|integer|min:1',
            'default_subscription_tier' => 'sometimes|in:basic,standard,premium,enterprise',
            'enable_trial_auto_conversion' => 'sometimes|boolean',
            'enable_email_notifications' => 'sometimes|boolean',
            'enable_auto_suspend_expired' => 'sometimes|boolean',
            'days_before_expiry_warning' => 'sometimes|integer|min:1|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Store in cache
            foreach ($request->all() as $key => $value) {
                Cache::put("system_settings.{$key}", $value, 86400); // 24 hours
            }

            return response()->json([
                'success' => true,
                'message' => 'System settings updated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('SuperAdmin: Failed to update system settings: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update system settings',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}