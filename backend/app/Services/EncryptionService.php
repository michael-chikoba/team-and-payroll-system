<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;

class EncryptionService
{
    /**
     * Field permission matrix.
     *
     * Any field NOT listed here defaults to ['employee', 'manager', 'admin', 'superadmin']
     * (i.e. any authenticated user can read it).  Listing a field here RESTRICTS it to
     * the specified roles only.  This is an allowlist for sensitive fields, not a denylist
     * for everything else.
     */
    protected array $fieldPermissions = [

        // ── Employee PII ──────────────────────────────────────────────────────
        'national_id'       => ['manager', 'admin', 'superadmin'],
        'emergency_contact' => ['manager', 'admin', 'superadmin'],
        'address'           => ['manager', 'admin', 'superadmin'],
        'date_of_birth'     => ['manager', 'admin', 'superadmin'],
        'bank_details'      => ['admin',   'superadmin'],

        // ── Employee financials ───────────────────────────────────────────────
        'base_salary'         => ['admin', 'superadmin'],
        'transport_allowance' => ['admin', 'superadmin'],
        'lunch_allowance'     => ['admin', 'superadmin'],

        // ── Payslip financials ────────────────────────────────────────────────
        // Employees must be able to read their own payslip — ownerId check
        // in decrypt() grants access before this list is consulted.
        // These restrictions apply only when reading ANOTHER person's payslip.
        'basic_salary'     => ['admin', 'superadmin'],
        'house_allowance'  => ['admin', 'superadmin'],
        'gross_salary'     => ['admin', 'superadmin'],
        'gross_pay'        => ['admin', 'superadmin'],
        'net_pay'          => ['admin', 'superadmin'],
        'tax_deductions'   => ['admin', 'superadmin'],
        'total_deductions' => ['admin', 'superadmin'],
        'napsa'            => ['admin', 'superadmin'],
        'paye'             => ['admin', 'superadmin'],
        'nhima'            => ['admin', 'superadmin'],
        'pension'          => ['admin', 'superadmin'],
        'other_deductions' => ['admin', 'superadmin'],
        'overtime_rate'    => ['admin', 'superadmin'],
        'overtime_pay'     => ['admin', 'superadmin'],

        // status and phone are readable by managers and above
        'status' => ['employee', 'manager', 'admin', 'superadmin'],
        'phone'  => ['manager',  'admin',   'superadmin'],

        // ── Business sensitive ────────────────────────────────────────────────
        'registration_number'       => ['admin', 'superadmin'],
        'tax_identification_number' => ['admin', 'superadmin'],

        // ── Contact fields — any authenticated user ───────────────────────────
        'email' => ['employee', 'manager', 'admin', 'superadmin'],
    ];

    /**
     * Mask patterns returned when a user lacks permission to read a field.
     */
    protected array $masks = [
        'base_salary'               => '***,***.**',
        'transport_allowance'       => '***.**',
        'lunch_allowance'           => '***.**',
        'bank_details'              => '{"bank":"[HIDDEN]","account":"****"}',
        'basic_salary'              => '***,***.**',
        'house_allowance'           => '***,***.**',
        'gross_salary'              => '***,***.**',
        'gross_pay'                 => '***,***.**',
        'net_pay'                   => '***,***.**',
        'tax_deductions'            => '***.**',
        'total_deductions'          => '***.**',
        'napsa'                     => '***.**',
        'paye'                      => '***.**',
        'nhima'                     => '***.**',
        'pension'                   => '***.**',
        'other_deductions'          => '***.**',
        'overtime_rate'             => '***.**',
        'overtime_pay'              => '***.**',
        'national_id'               => '**-******-*',
        'phone'                     => '+** *** *** ****',
        'emergency_contact'         => '[RESTRICTED]',
        'address'                   => '[RESTRICTED ADDRESS]',
        'registration_number'       => 'REG-******',
        'tax_identification_number' => 'TIN-******',
        'email'                     => '***@***.**',
        'status'                    => '[RESTRICTED]',
    ];

    // =========================================================================
    // PUBLIC API
    // =========================================================================

    /**
     * Encrypt a value for storage. Returns the original value if it is already
     * encrypted, null, or an empty string.
     */
    public function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if ($this->isEncrypted($value)) {
            return $value;
        }

        try {
            return Crypt::encryptString($value);
        } catch (\Exception $e) {
            Log::error('EncryptionService: encryption failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Decrypt a value, enforcing role-based access control.
     *
     * Access is granted when ANY of the following is true (evaluated in order):
     *   1. The value is not actually encrypted (plaintext legacy data) → return as-is
     *   2. The authenticated user is a superadmin → full access
     *   3. The authenticated user IS the record owner (ownerId matches) → full access
     *   4. The user's role appears in $fieldPermissions[$field] → full access
     *   5. The field is NOT listed in $fieldPermissions → full access (open by default)
     *   6. None of the above → return masked value
     *
     * @param  string|null  $encrypted  Raw ciphertext from the database
     * @param  string       $field      Field name (permission lookup + mask fallback)
     * @param  int|null     $ownerId    user_id of the record owner (employees read own)
     */
    public function decrypt(?string $encrypted, string $field, ?int $ownerId = null): ?string
    {
        if ($encrypted === null || $encrypted === '') {
            return $encrypted;
        }

        if (!is_string($encrypted)) {
            Log::warning('EncryptionService: non-string passed to decrypt()', [
                'field' => $field,
                'type'  => gettype($encrypted),
            ]);
            return is_array($encrypted) ? json_encode($encrypted) : (string) $encrypted;
        }

        // Not actually encrypted (legacy plaintext) — return as-is immediately
        if (!$this->isEncrypted($encrypted)) {
            return $encrypted;
        }

        $user = Auth::user();

        // Unauthenticated request → always mask
        if (!$user) {
            return $this->mask($field);
        }

        // ── Superadmin: unconditional access ──────────────────────────────────
        if ($user->is_superadmin ?? false) {
            return $this->decryptRaw($encrypted);
        }

        $role   = $user->role ?? 'employee';
        $userId = $user->id;

        // ── Owner access: employee reading their own record ───────────────────
        if ($ownerId !== null && $userId === $ownerId) {
            return $this->decryptRaw($encrypted);
        }

        // ── Fields NOT in the permissions matrix: open to any authenticated user
        if (!array_key_exists($field, $this->fieldPermissions)) {
            return $this->decryptRaw($encrypted);
        }

        // ── Fields in the matrix: check role ──────────────────────────────────
        $allowedRoles = $this->fieldPermissions[$field];
        if (in_array($role, $allowedRoles, true)) {
            return $this->decryptRaw($encrypted);
        }

        // No permission
        Log::debug('EncryptionService: access denied', [
            'field'  => $field,
            'role'   => $role,
            'userId' => $userId,
        ]);

        return $this->mask($field);
    }

    /**
     * Raw decryption with no role checks.
     * Use only in trusted server-side contexts (exports, PDF generation, commands).
     */
    public function decryptRaw(?string $encrypted): ?string
    {
        if ($encrypted === null || $encrypted === '') {
            return $encrypted;
        }

        if (!$this->isEncrypted($encrypted)) {
            return $encrypted; // plaintext legacy value
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException $e) {
            Log::warning('EncryptionService: decryption failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Returns true if the authenticated user is permitted to read $field.
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

        // Fields not in the matrix are open to any authenticated user
        if (!array_key_exists($field, $this->fieldPermissions)) {
            return true;
        }

        $role = $user->role ?? 'employee';
        return in_array($role, $this->fieldPermissions[$field], true);
    }

    /**
     * Detect whether a string is a Laravel Crypt::encryptString() payload.
     * Laravel encrypts to base64(JSON{iv, value, mac}).
     */
    public function isEncrypted(?string $value): bool
    {
        if ($value === null || strlen($value) < 20) {
            return false;
        }

        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return false;
        }

        $json = json_decode($decoded, true);

        return is_array($json)
            && isset($json['iv'], $json['value'], $json['mac']);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    protected function mask(string $field): string
    {
        return $this->masks[$field] ?? '[ENCRYPTED]';
    }
}