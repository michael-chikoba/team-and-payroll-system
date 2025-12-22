<?php

namespace App\Rules;

use App\Models\SystemSetting;
use Illuminate\Contracts\Validation\Rule;

class ValidDepartment implements Rule
{
    protected $businessId;
    protected $countryCode;

    public function __construct($businessId = null, $countryCode = null)
    {
        $this->businessId = $businessId;
        $this->countryCode = $countryCode;
    }

    public function passes($attribute, $value)
    {
        $validDepartments = SystemSetting::getValidDepartments(
            $this->businessId, 
            $this->countryCode
        );

        return in_array($value, $validDepartments);
    }

    public function message()
    {
        return 'The selected department is invalid or not configured in system settings.';
    }
}