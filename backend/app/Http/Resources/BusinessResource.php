<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isSuperAdmin = $user->is_superadmin ?? false;
        $isAdmin = $this->hasAdmin($user) || $isSuperAdmin;

        // Base data everyone can see
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'business_type' => $this->business_type,
            'industry' => $this->industry,
            'logo' => $this->logo_path ? asset('storage/' . $this->logo_path) : null,
            'country_id' => $this->country_id,
            'currency_code' => $this->currency_code,
            'status' => $this->status,
            'is_verified' => $this->is_verified,
        ];

        // Admin can see contact details
        if ($isAdmin) {
            $data['email'] = $this->email;
            $data['phone'] = $this->phone;
            $data['fax'] = $this->fax;
            $data['address'] = $this->full_address;
            $data['website'] = $this->website;
        }

        // SuperAdmin can see sensitive data
        if ($isSuperAdmin) {
            $data['registration_number'] = $this->registration_number;
            $data['tax_identification_number'] = $this->tax_identification_number;
            $data['employee_limit'] = $this->employee_limit;
            $data['current_employee_count'] = $this->current_employee_count;
            $data['subscription_tier'] = $this->subscription_tier;
            $data['subscription_end_date'] = $this->subscription_end_date;
        }

        return $data;
    }
}