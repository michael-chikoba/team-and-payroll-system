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
     * Record an activity heartbeat from the frontend.
     *
     * The response tells the frontend exactly what state the overtime session
     * is in so it can render the appropriate UI (nothing / warning banner / countdown).
     *
     * POST /api/attendance/heartbeat
     */
    public function heartbeat(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee profile not found.',
                ], 404);
            }

            $metadata = [
                'user_agent'   => $request->userAgent(),
                'ip_address'   => $request->ip(),
                // Frontend should send these based on Page Visibility API + user interaction events.
                'screen_active'=> $request->boolean('screen_active', true),
                'page_visible' => $request->boolean('page_visible', true),
            ];

            $result = $this->activityService->recordHeartbeat($employee, $metadata);

            // If the session is in 'closing' state, return 202 so the frontend
            // can distinguish "recorded fine" (200) from "you're about to be closed" (202).
            $httpStatus = ($result['idle_status'] ?? 'active') === 'closing' ? 202 : 200;

            return response()->json($result, $httpStatus);

        } catch (\Exception $e) {
            Log::error('Heartbeat endpoint failed', [
                'user_id' => $request->user()->id,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record heartbeat.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the current activity / idle status for the authenticated employee.
     *
     * GET /api/attendance/activity-status
     */
    public function getStatus(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee profile not found.',
                ], 404);
            }

            $status = $this->activityService->getActivityStatus($employee);

            return response()->json([
                'success' => true,
                'data'    => $status,
            ]);

        } catch (\Exception $e) {
            Log::error('Activity status failed', [
                'user_id' => $request->user()->id,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get activity status.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * "I'm still working" — manual activity refresh.
     * Clears any pending idle warning and resets the countdown.
     *
     * POST /api/attendance/refresh-activity
     */
    public function refreshActivity(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee profile not found.',
                ], 404);
            }

            $result = $this->activityService->forceRefreshActivity($employee);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Refresh activity failed', [
                'user_id' => $request->user()->id,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh activity.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sweep all open overtime sessions and apply two-stage idle logic.
     * Called by the scheduler every minute — should be protected by middleware
     * (e.g., internal-only or signed URL) in production.
     *
     * POST /api/attendance/check-idle-sessions
     */
    public function checkIdleSessions(Request $request): JsonResponse
    {
        try {
            $results = $this->activityService->checkIdleSessions();

            return response()->json([
                'success' => true,
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('Check idle sessions failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check idle sessions.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}