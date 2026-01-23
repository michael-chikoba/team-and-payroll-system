<?php
// app/Http/Controllers/Api/ActivityTrackingController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActivityTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActivityTrackingController extends Controller
{
    public function __construct(
        private ActivityTrackingService $activityService
    ) {}

    /**
     * Record activity heartbeat from frontend
     * POST /api/attendance/heartbeat
     */
    public function heartbeat(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Optional metadata from client
            $metadata = [
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'screen_active' => $request->input('screen_active', true),
                'page_visible' => $request->input('page_visible', true),
            ];

            $result = $this->activityService->recordHeartbeat($employee, $metadata);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Heartbeat endpoint failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record heartbeat',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current activity status
     * GET /api/attendance/activity-status
     */
    public function getStatus(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee profile not found'
                ], 404);
            }

            $status = $this->activityService->getActivityStatus($employee);

            return response()->json([
                'success' => true,
                'data' => $status
            ]);

        } catch (\Exception $e) {
            Log::error('Activity status failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get activity status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manually refresh activity
     * POST /api/attendance/refresh-activity
     */
    public function refreshActivity(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee profile not found'
                ], 404);
            }

            $result = $this->activityService->forceRefreshActivity($employee);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Refresh activity failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh activity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check all idle sessions (admin/cron)
     * POST /api/attendance/check-idle-sessions
     */
    public function checkIdleSessions(Request $request): JsonResponse
    {
        try {
            $results = $this->activityService->checkIdleSessions();

            return response()->json([
                'success' => true,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Check idle sessions failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check idle sessions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}