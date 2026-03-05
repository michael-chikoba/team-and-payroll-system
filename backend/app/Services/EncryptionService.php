<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;
class EncryptionService
{
    /**
     * Field permissions matrix - updated for your models
     */
    protected array $fieldPermissions = [
        // Employee financial fields (admin + superadmin only)
        'base_salary' => ['admin', 'superadmin'],
        'transport_allowance' => ['admin', 'superadmin'],
        'lunch_allowance' => ['admin', 'superadmin'],
        'bank_details' => ['admin', 'superadmin'],
        
        // Payslip financial fields (admin + superadmin only)
        'basic_salary' => ['admin', 'superadmin'],
        'gross_pay' => ['admin', 'superadmin'],
        'net_pay' => ['admin', 'superadmin'],
        'tax_deductions' => ['admin', 'superadmin'],
        
        // Personal identifiable information (manager+)
        'national_id' => ['manager', 'admin', 'superadmin'],
        'phone' => ['manager', 'admin', 'superadmin'],
        'emergency_contact' => ['manager', 'admin', 'superadmin'],
        'address' => ['manager', 'admin', 'superadmin'],
        'date_of_birth' => ['manager', 'admin', 'superadmin'],
        
        // Business sensitive (admin+)
        'registration_number' => ['admin', 'superadmin'],
        'tax_identification_number' => ['admin', 'superadmin'],
        'email' => ['employee', 'manager', 'admin', 'superadmin'],
    ];

    /**
     * Mask patterns for unauthorized access
     */
    protected array $masks = [
        'base_salary' => '***,***.**',
        'transport_allowance' => '***.**',
        'lunch_allowance' => '***.**',
        'bank_details' => '{"bank":"[HIDDEN]","account":"****"}',
        'basic_salary' => '***,***.**',
        'gross_pay' => '***,***.**',
        'net_pay' => '***,***.**',
        'tax_deductions' => '***.**',
        'national_id' => '**-******-*',
        'phone' => '+** *** *** ****',
        'emergency_contact' => '[RESTRICTED]',
        'address' => '[RESTRICTED ADDRESS]',
        'registration_number' => 'REG-******',
        'tax_identification_number' => 'TIN-******',
        'email' => '***@***.**',
    ];

    /**
     * Encrypt data for storage
     */
    public function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        // Prevent double encryption
        if ($this->isEncrypted($value)) {
            return $value;
        }

        try {
            return Crypt::encryptString($value);
        } catch (\Exception $e) {
            \Log::error('Encryption failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

   /**
 * Decrypt a value — checks role permission first.
 *
 * @param  string|null  $encrypted  The raw ciphertext from the database
 * @param  string       $field      Field name (used for permission lookup + masking)
 * @param  int|null     $ownerId    The user_id that owns this record (employees can read own)
 * @return string|null
 */
public function decrypt(?string $encrypted, string $field, ?int $ownerId = null): ?string
{
    // If it's null or empty, return as is
    if ($encrypted === null || $encrypted === '') {
        return $encrypted;
    }

    // Ensure we're working with a string
    if (!is_string($encrypted)) {
        Log::warning('EncryptionService: Non-string value passed to decrypt', [
            'field' => $field,
            'type' => gettype($encrypted)
        ]);
        return is_array($encrypted) ? json_encode($encrypted) : (string) $encrypted;
    }

    $user = Auth::user();

    // Unauthenticated → always mask
    if (!$user) {
        return $this->mask($field);
    }

    // Superadmin → full access always
    if ($user->is_superadmin ?? false) {
        return $this->decryptRaw($encrypted);
    }

    $role = $user->role ?? 'employee';
    $userId = $user->id;

    // Employee reading their OWN record → grant access
    if ($role === 'employee' && $ownerId !== null && $userId === $ownerId) {
        return $this->decryptRaw($encrypted);
    }

    // Check role permission map
    $allowedRoles = $this->fieldPermissions[$field] ?? [];
    if (in_array($role, $allowedRoles, true)) {
        return $this->decryptRaw($encrypted);
    }

    // No permission → return masked value
    return $this->mask($field);
}
    /**
     * Raw decryption without role checks (internal use only)
     */
    public function decryptRaw(?string $encrypted): ?string
    {
        if ($encrypted === null || $encrypted === '') {
            return $encrypted;
        }

        if (!$this->isEncrypted($encrypted)) {
            return $encrypted; // Plaintext legacy data
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException $e) {
            \Log::warning('Decryption failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Check if user can decrypt a field
     */
    public function canDecrypt(string $field, ?int $ownerId = null): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        if ($user->is_superadmin ?? false) {
            return true;
        }

        if ($ownerId !== null && $user->id === $ownerId) {
            return true;
        }

        $role = $user->role ?? 'employee';
        $allowedRoles = $this->fieldPermissions[$field] ?? [];
        
        return in_array($role, $allowedRoles, true);
    }

    /**
     * Detect if a string is already encrypted (Laravel format)
     */
    public function isEncrypted(?string $value): bool
    {
        if ($value === null || strlen($value) < 20) {
            return false;
        }

        // Laravel encrypted strings are base64 encoded JSON with 'iv', 'value', 'mac'
        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return false;
        }

        $json = json_decode($decoded, true);
        return is_array($json) && 
               isset($json['iv'], $json['value'], $json['mac']);
    }

    /**
     * Get masked value for a field
     */
    protected function mask(string $field): string
    {
        return $this->masks[$field] ?? '[ENCRYPTED]';
    }
}