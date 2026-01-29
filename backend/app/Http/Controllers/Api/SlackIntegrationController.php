<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SlackIntegration;
use App\Services\SlackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SlackIntegrationController extends Controller
{
    protected $slackService;

    public function __construct(SlackService $slackService)
    {
        $this->slackService = $slackService;
    }

    /**
     * Get current Slack integration for user's business
     */
    public function index(Request $request)
    {

    Log::info('Slack integration request received', [
        'user_id' => Auth::id(),
        'user' => Auth::user(),
        'headers' => $request->headers->all()
    ]);

        $user = Auth::user();
         if (!$user) {
        Log::error('No authenticated user found');
        return response()->json(['message' => 'Unauthenticated'], 401);
    }
        $employee = $user->employee;

        if (!$employee) {
            Log::error('No employee found for user', ['user_id' => $user->id]);
            return response()->json([
                'message' => 'Employee profile not found'
            ], 403);
        }

        $integration = SlackIntegration::where('business_id', $employee->business_id)
            ->with('connectedBy:id,first_name,last_name,email')
            ->first();

        if (!$integration) {
            return response()->json([
                'integration' => null,
                'is_configured' => false
            ]);
        }

        return response()->json([
            'integration' => [
                'id' => $integration->id,
                'workspace_name' => $integration->workspace_name,
                'workspace_id' => $integration->workspace_id,
                'channel_name' => $integration->channel_name,
                'channel_id' => $integration->channel_id,
                'is_active' => $integration->is_active,
                'connected_at' => $integration->connected_at,
                'connected_by' => $integration->connectedBy ? [
                    'name' => trim("{$integration->connectedBy->first_name} {$integration->connectedBy->last_name}"),
                    'email' => $integration->connectedBy->email
                ] : null,
                'notification_settings' => $integration->notification_settings ?? $this->getDefaultNotificationSettings()
            ],
            'is_configured' => true
        ]);
    }

    /**
     * Store new Slack integration
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole('admin')) {
            return response()->json([
                'message' => 'Only admins can configure Slack integration'
            ], 403);
        }

        $employee = $user->employee;
        if (!$employee) {
            return response()->json([
                'message' => 'Employee profile not found'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'webhook_url' => 'required|url|starts_with:https://hooks.slack.com/',
            'channel_name' => 'required|string|max:255',
            'channel_id' => 'required|string|max:255',
            'workspace_name' => 'nullable|string|max:255',
            'workspace_id' => 'nullable|string|max:255',
            'notification_settings' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if integration already exists
            $existing = SlackIntegration::where('business_id', $employee->business_id)->first();
            
            if ($existing) {
                return response()->json([
                    'message' => 'Slack integration already exists. Please update or delete it first.'
                ], 409);
            }

            $integration = SlackIntegration::create([
                'business_id' => $employee->business_id,
                'webhook_url' => $request->webhook_url,
                'channel_name' => $request->channel_name,
                'channel_id' => $request->channel_id,
                'workspace_name' => $request->workspace_name,
                'workspace_id' => $request->workspace_id,
                'notification_settings' => $request->notification_settings ?? $this->getDefaultNotificationSettings(),
                'is_active' => true,
                'connected_at' => now(),
                'connected_by' => $user->id
            ]);

            // Test the connection
            $testResult = $this->slackService->testConnection($integration);

            if (!$testResult['success']) {
                $integration->delete();
                return response()->json([
                    'message' => 'Failed to connect to Slack',
                    'error' => $testResult['error'] ?? 'Unknown error'
                ], 400);
            }

            Log::info('Slack integration created', [
                'business_id' => $employee->business_id,
                'user_id' => $user->id,
                'channel' => $request->channel_name
            ]);

            return response()->json([
                'message' => 'Slack integration configured successfully',
                'integration' => $integration
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create Slack integration', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to configure Slack integration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update Slack integration
     */
    public function update(Request $request, SlackIntegration $slackIntegration)
    {
        $user = Auth::user();
        
        if (!$user->hasRole('admin')) {
            return response()->json([
                'message' => 'Only admins can update Slack integration'
            ], 403);
        }

        $employee = $user->employee;
        if (!$employee || $employee->business_id !== $slackIntegration->business_id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'webhook_url' => 'sometimes|required|url|starts_with:https://hooks.slack.com/',
            'channel_name' => 'sometimes|required|string|max:255',
            'channel_id' => 'sometimes|required|string|max:255',
            'workspace_name' => 'nullable|string|max:255',
            'workspace_id' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
            'notification_settings' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $slackIntegration->update($request->only([
                'webhook_url',
                'channel_name',
                'channel_id',
                'workspace_name',
                'workspace_id',
                'is_active',
                'notification_settings'
            ]));

            // Test connection if webhook was updated
            if ($request->has('webhook_url')) {
                $testResult = $this->slackService->testConnection($slackIntegration);
                
                if (!$testResult['success']) {
                    return response()->json([
                        'message' => 'Integration updated but connection test failed',
                        'error' => $testResult['error'] ?? 'Unknown error',
                        'integration' => $slackIntegration
                    ], 200);
                }
            }

            Log::info('Slack integration updated', [
                'integration_id' => $slackIntegration->id,
                'user_id' => $user->id
            ]);

            return response()->json([
                'message' => 'Slack integration updated successfully',
                'integration' => $slackIntegration
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update Slack integration', [
                'integration_id' => $slackIntegration->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to update Slack integration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Slack integration
     */
    public function destroy(SlackIntegration $slackIntegration)
    {
        $user = Auth::user();
        
        if (!$user->hasRole('admin')) {
            return response()->json([
                'message' => 'Only admins can delete Slack integration'
            ], 403);
        }

        $employee = $user->employee;
        if (!$employee || $employee->business_id !== $slackIntegration->business_id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $slackIntegration->delete();

            Log::info('Slack integration deleted', [
                'integration_id' => $slackIntegration->id,
                'user_id' => $user->id
            ]);

            return response()->json([
                'message' => 'Slack integration deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete Slack integration', [
                'integration_id' => $slackIntegration->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to delete Slack integration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test Slack connection
     */
    public function test(SlackIntegration $slackIntegration)
    {
        $user = Auth::user();
        
        if (!$user->hasRole('admin')) {
            return response()->json([
                'message' => 'Only admins can test Slack integration'
            ], 403);
        }

        $employee = $user->employee;
        if (!$employee || $employee->business_id !== $slackIntegration->business_id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $result = $this->slackService->testConnection($slackIntegration);

        return response()->json($result);
    }

    /**
     * Get notification logs
     */
    public function logs(Request $request, SlackIntegration $slackIntegration)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee || $employee->business_id !== $slackIntegration->business_id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $logs = $slackIntegration->logs()
            ->with('ticket:id,title,type,status')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($logs);
    }

    /**
     * Get default notification settings
     */
    protected function getDefaultNotificationSettings(): array
    {
        return [
            'created' => true,
            'approved' => true,
            'rejected' => true,
            'status_changed' => true,
            'assigned' => true,
            'comment_added' => false,
            'attachment_uploaded' => false
        ];
    }
}