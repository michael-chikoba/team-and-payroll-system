<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\BusinessGroup;
use App\Models\Business;

class CheckBusinessGroupAccess
{
    public function handle(Request $request, Closure $next, string $permission = null)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $employee = $user->employee;
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'No business association found'
            ], 403);
        }

        $business = $employee->business;
        $businessGroup = $request->route('businessGroup');

        if (!$businessGroup instanceof BusinessGroup) {
            $businessGroup = BusinessGroup::find($request->route('businessGroup'));
        }

        if (!$businessGroup) {
            return response()->json([
                'success' => false,
                'message' => 'Business group not found'
            ], 404);
        }

        // Check if business is member of group
        if (!$business->isInGroup($businessGroup->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Your business is not a member of this group'
            ], 403);
        }

        // Check specific permission if provided
        if ($permission) {
            $hasPermission = match($permission) {
                'manage' => $businessGroup->canBusinessManageGroup($business->id),
                'invite' => $businessGroup->canBusinessInviteOthers($business->id),
                default => true,
            };

            if (!$hasPermission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }
        }

        return $next($request);
    }
}