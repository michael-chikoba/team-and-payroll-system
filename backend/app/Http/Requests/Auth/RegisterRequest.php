<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        Log::debug('RegisterRequest authorization check', [
            'ip' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'method' => $this->method(),
            'authorized' => true,
        ]);

        return true;
    }

    public function rules(): array
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:employee,manager,admin',
        ];

        Log::debug('RegisterRequest rules defined', [
            'rules_count' => count($rules),
            'fields' => array_keys($rules),
            'has_password_confirmation' => isset($rules['password']) && str_contains($rules['password'], 'confirmed'),
        ]);

        return $rules;
    }

    public function messages(): array
    {
        $messages = [
            'first_name.required' => 'First name is required',
            'last_name.required' => 'Last name is required',
            'email.required' => 'Email is required',
            'email.unique' => 'This email is already registered',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Password confirmation does not match',
            'role.required' => 'Role is required',
            'role.in' => 'Role must be employee, manager, or admin',
        ];

        Log::debug('RegisterRequest custom messages defined', [
            'custom_messages_count' => count($messages),
        ]);

        return $messages;
    }

    /**
     * Prepare the data for validation.
     */
   // In your RegisterRequest prepareForValidation() or controller
protected function prepareForValidation()
{
    $data = [
        'first_name' => $this->firstName,
        'last_name' => $this->lastName,
        'name' => trim($this->firstName . ' ' . $this->lastName), // Add this
        'password_confirmation' => $this->confirmPassword,
        'email' => strtolower(trim($this->email)),
        'password' => $this->password,
        'role' => $this->role,
    ];

    $this->merge($data);
}
    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'email' => 'email address',
            'password' => 'password',
            'role' => 'user role',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();
        $errorCount = count($validator->errors()->all());

        Log::warning('RegisterRequest validation failed', [
            'ip' => $this->ip(),
            'email_attempted' => $this->input('email'),
            'role_attempted' => $this->input('role'),
            'error_count' => $errorCount,
            'error_fields' => array_keys($errors),
            'specific_errors' => $errors,
            'user_agent' => $this->userAgent(),
            'original_fields_received' => array_keys($this->all()),
        ]);

        // Check for specific common validation failures
        $this->logSpecificValidationIssues($validator);

        throw new ValidationException($validator);
    }

    /**
     * Log specific validation issues for better monitoring.
     */
    private function logSpecificValidationIssues(Validator $validator): void
    {
        $errors = $validator->errors();

        // Log duplicate email attempts
        if ($errors->has('email') && str_contains(implode(' ', $errors->get('email')), 'already')) {
            Log::notice('Duplicate email registration attempt', [
                'email' => $this->input('email'),
                'ip' => $this->ip(),
            ]);
        }

        // Log password strength issues
        if ($errors->has('password')) {
            $passwordErrors = $errors->get('password');
            
            if (in_array('Password must be at least 8 characters', $passwordErrors)) {
                Log::info('Password too short during registration', [
                    'email' => $this->input('email'),
                    'password_length' => strlen($this->input('password') ?? ''),
                ]);
            }

            if (in_array('Password confirmation does not match', $passwordErrors)) {
                Log::info('Password confirmation mismatch during registration', [
                    'email' => $this->input('email'),
                    'has_password_confirmation' => !empty($this->input('password_confirmation')),
                    'password_value_length' => strlen($this->input('password') ?? ''),
                    'confirmation_value_length' => strlen($this->input('password_confirmation') ?? ''),
                ]);
            }
        }

        // Log missing required fields
        if ($errors->has('first_name')) {
            Log::info('First name missing during registration', [
                'email' => $this->input('email'),
                'original_firstName_provided' => !empty($this->get('firstName')),
            ]);
        }

        if ($errors->has('last_name')) {
            Log::info('Last name missing during registration', [
                'email' => $this->input('email'),
                'original_lastName_provided' => !empty($this->get('lastName')),
            ]);
        }

        // Log invalid role attempts
        if ($errors->has('role')) {
            Log::info('Invalid role attempted during registration', [
                'email' => $this->input('email'),
                'role_attempted' => $this->input('role'),
                'allowed_roles' => ['employee', 'manager', 'admin'],
            ]);
        }
    }

    /**
     * Validation passed hook.
     */
    protected function passedValidation(): void
    {
        Log::info('RegisterRequest validation passed', [
            'email' => $this->input('email'),
            'role' => $this->input('role'),
            'first_name_provided' => !empty($this->input('first_name')),
            'last_name_provided' => !empty($this->input('last_name')),
            'password_confirmation_provided' => !empty($this->input('password_confirmation')),
            'ip' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'field_mapping_successful' => true,
        ]);

        // Log role-specific registration attempts for monitoring
        $role = $this->input('role');
        if (in_array($role, ['admin', 'manager'])) {
            Log::notice('Elevated role registration attempt', [
                'email' => $this->input('email'),
                'role' => $role,
                'ip' => $this->ip(),
            ]);
        }
    }

    /**
     * Get the validated data with logging.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        if (is_null($key)) {
            Log::debug('RegisterRequest validated data retrieved', [
                'validated_fields' => array_keys($validated),
                'has_password' => isset($validated['password']),
                'password_set' => !empty($validated['password']),
                'has_password_confirmation' => isset($validated['password_confirmation']),
                // Never log actual password values
            ]);
        }

        return $validated;
    }
}