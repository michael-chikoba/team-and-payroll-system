<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DemoRequest;
use App\Models\ContactRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PublicController extends Controller
{
    /**
     * Store a demo request
     */
    public function storeDemoRequest(Request $request): JsonResponse
    {
        Log::info('PUBLIC: Demo request received', [
            'email' => $request->email,
            'company' => $request->company_name,
            'ip' => $request->ip(),
        ]);

        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'employee_count' => 'nullable|string|max:50',
            'message' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            Log::warning('PUBLIC: Demo request validation failed', [
                'errors' => $validator->errors()->toArray(),
                'email' => $request->email,
            ]);

            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $demoRequest = DemoRequest::create($validator->validated());

            Log::info('PUBLIC: Demo request created', [
                'id' => $demoRequest->id,
                'email' => $demoRequest->email,
                'company' => $demoRequest->company_name,
            ]);

            // TODO: Send notification email to sales team
            // TODO: Send confirmation email to requester

            return response()->json([
                'message' => 'Demo request submitted successfully',
                'data' => $demoRequest
            ], 201);

        } catch (\Exception $e) {
            Log::error('PUBLIC: Failed to create demo request', [
                'error' => $e->getMessage(),
                'email' => $request->email,
            ]);

            return response()->json([
                'message' => 'Failed to submit demo request'
            ], 500);
        }
    }

    /**
     * Store a contact request
     */
    public function storeContactRequest(Request $request): JsonResponse
    {
        Log::info('PUBLIC: Contact request received', [
            'email' => $request->email,
            'subject' => $request->subject,
            'ip' => $request->ip(),
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            Log::warning('PUBLIC: Contact request validation failed', [
                'errors' => $validator->errors()->toArray(),
                'email' => $request->email,
            ]);

            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $contactRequest = ContactRequest::create($validator->validated());

            Log::info('PUBLIC: Contact request created', [
                'id' => $contactRequest->id,
                'email' => $contactRequest->email,
                'subject' => $contactRequest->subject,
            ]);

            // TODO: Send notification email to support team
            // TODO: Send confirmation email to requester

            return response()->json([
                'message' => 'Contact request submitted successfully',
                'data' => $contactRequest
            ], 201);

        } catch (\Exception $e) {
            Log::error('PUBLIC: Failed to create contact request', [
                'error' => $e->getMessage(),
                'email' => $request->email,
            ]);

            return response()->json([
                'message' => 'Failed to submit contact request'
            ], 500);
        }
    }

    /**
     * Get all demo requests (Admin only)
     */
    public function getDemoRequests(Request $request): JsonResponse
    {
        try {
            // Check if user is admin
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 403);
            }

            $query = DemoRequest::query();

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by employee count
            if ($request->has('employee_count')) {
                $query->where('employee_count', $request->employee_count);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('company_name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            $demoRequests = $query->orderBy('created_at', 'desc')->get();

            Log::info('ADMIN: Demo requests fetched', [
                'user_id' => $request->user()->id,
                'count' => $demoRequests->count(),
            ]);

            return response()->json([
                'data' => $demoRequests
            ]);

        } catch (\Exception $e) {
            Log::error('ADMIN: Failed to fetch demo requests', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to fetch demo requests'
            ], 500);
        }
    }

    /**
     * Get single demo request (Admin only)
     */
    public function getDemoRequest(Request $request, $id): JsonResponse
    {
        try {
            if ($request->user()->role !== 'admin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $demoRequest = DemoRequest::findOrFail($id);

            return response()->json([
                'data' => $demoRequest
            ]);

        } catch (\Exception $e) {
            Log::error('ADMIN: Failed to fetch demo request', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Demo request not found'
            ], 404);
        }
    }

    /**
     * Update demo request status (Admin only)
     */
    public function updateDemoRequest(Request $request, $id): JsonResponse
    {
        try {
            if ($request->user()->role !== 'admin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $demoRequest = DemoRequest::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,contacted,completed,cancelled',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $demoRequest->status = $request->status;
            
            // Set contacted_at timestamp when status changes to contacted
            if ($request->status === 'contacted' && !$demoRequest->contacted_at) {
                $demoRequest->contacted_at = now();
            }

            $demoRequest->save();

            Log::info('ADMIN: Demo request updated', [
                'id' => $id,
                'status' => $request->status,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Demo request updated successfully',
                'data' => $demoRequest
            ]);

        } catch (\Exception $e) {
            Log::error('ADMIN: Failed to update demo request', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to update demo request'
            ], 500);
        }
    }

    /**
     * Delete demo request (Admin only)
     */
    public function deleteDemoRequest(Request $request, $id): JsonResponse
    {
        try {
            if ($request->user()->role !== 'admin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $demoRequest = DemoRequest::findOrFail($id);
            $demoRequest->delete();

            Log::info('ADMIN: Demo request deleted', [
                'id' => $id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Demo request deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('ADMIN: Failed to delete demo request', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to delete demo request'
            ], 500);
        }
    }

    /**
     * Get all contact requests (Admin only)
     */
    public function getContactRequests(Request $request): JsonResponse
    {
        try {
            if ($request->user()->role !== 'admin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $query = ContactRequest::query();

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('subject', 'like', "%{$search}%");
                });
            }

            $contactRequests = $query->orderBy('created_at', 'desc')->get();

            Log::info('ADMIN: Contact requests fetched', [
                'user_id' => $request->user()->id,
                'count' => $contactRequests->count(),
            ]);

            return response()->json([
                'data' => $contactRequests
            ]);

        } catch (\Exception $e) {
            Log::error('ADMIN: Failed to fetch contact requests', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to fetch contact requests'
            ], 500);
        }
    }

    /**
     * Get single contact request (Admin only)
     */
    public function getContactRequest(Request $request, $id): JsonResponse
    {
        try {
            if ($request->user()->role !== 'admin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $contactRequest = ContactRequest::findOrFail($id);

            return response()->json([
                'data' => $contactRequest
            ]);

        } catch (\Exception $e) {
            Log::error('ADMIN: Failed to fetch contact request', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Contact request not found'
            ], 404);
        }
    }

    /**
     * Update contact request status (Admin only)
     */
    public function updateContactRequest(Request $request, $id): JsonResponse
    {
        try {
            if ($request->user()->role !== 'admin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $contactRequest = ContactRequest::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,responded,closed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $contactRequest->status = $request->status;
            
            // Set responded_at timestamp when status changes to responded
            if ($request->status === 'responded' && !$contactRequest->responded_at) {
                $contactRequest->responded_at = now();
            }

            $contactRequest->save();

            Log::info('ADMIN: Contact request updated', [
                'id' => $id,
                'status' => $request->status,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Contact request updated successfully',
                'data' => $contactRequest
            ]);

        } catch (\Exception $e) {
            Log::error('ADMIN: Failed to update contact request', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to update contact request'
            ], 500);
        }
    }

    /**
     * Delete contact request (Admin only)
     */
    public function deleteContactRequest(Request $request, $id): JsonResponse
    {
        try {
            if ($request->user()->role !== 'admin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $contactRequest = ContactRequest::findOrFail($id);
            $contactRequest->delete();

            Log::info('ADMIN: Contact request deleted', [
                'id' => $id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Contact request deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('ADMIN: Failed to delete contact request', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to delete contact request'
            ], 500);
        }
    }
}