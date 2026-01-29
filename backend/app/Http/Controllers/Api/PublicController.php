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
}