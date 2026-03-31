<?php

namespace App\Traits;

use App\Services\EncryptionService;
use Illuminate\Support\Facades\Log;

trait HasEncryptedFields
{
    // =========================================================================
    // BOOT — encrypt on save
    // =========================================================================

    public static function bootHasEncryptedFields(): void
    {
        static::saving(function ($model) {
            $service = app(EncryptionService::class);

            foreach ($model->getEncryptedFields() as $field) {
                if (!array_key_exists($field, $model->attributes)) {
                    continue;
                }

                $value = $model->attributes[$field];

                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }

                if ($value !== null && $value !== '' && !$service->isEncrypted((string) $value)) {
                    try {
                        $model->attributes[$field] = $service->encrypt((string) $value);
                    } catch (\Exception $e) {
                        Log::error("Failed to encrypt field {$field}", [
                            'model' => get_class($model),
                            'error' => $e->getMessage(),
                        ]);
                        throw $e;
                    }
                }
            }
        });
    }

    // =========================================================================
    // READ — decrypt on every property / array / JSON access
    // =========================================================================

    /**
     * Called by Laravel for $model->field access.
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);
        return $this->decryptIfNeeded($key, $value);
    }

    /**
     * Called by toArray() → toJson() → API responses.
     * Without this override Laravel serialises straight from $this->attributes,
     * bypassing getAttribute() and returning raw ciphertext to the frontend.
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        foreach ($this->getEncryptedFields() as $field) {
            if (!array_key_exists($field, $array)) {
                continue;
            }
            $array[$field] = $this->decryptIfNeeded($field, $array[$field]);
        }

        return $array;
    }

    // =========================================================================
    // WRITE
    // =========================================================================

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->getEncryptedFields(), true)) {
            $service = app(EncryptionService::class);

            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }

            if (is_string($value) && $value !== '' && !$service->isEncrypted($value)) {
                $this->attributes[$key] = $service->encrypt($value);
                return $this;
            }
        }

        return parent::setAttribute($key, $value);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Core decrypt logic shared by getAttribute() and toArray().
     *
     * Owner resolution:
     *   - For the User model:      ownerId = the record's own id
     *   - For the Payslip model:   ownerId = employee->user_id  (NOT employee_id)
     *   - For other models:        ownerId = user_id column if present, else null
     *
     * This means an employee (userId=60) viewing their own payslip
     * (payslip->employee->user_id=60) passes the owner check and gets
     * their own financial data decrypted.
     */
    private function decryptIfNeeded(string $key, mixed $value): mixed
    {
        if (!in_array($key, $this->getEncryptedFields(), true)) {
            return $value;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $service = app(EncryptionService::class);

            if ($service->isEncrypted($value)) {
                $ownerId   = $this->resolveOwnerId();
                $decrypted = $service->decrypt($value, $key, $ownerId);

                if ($this->isJsonField($key) && is_string($decrypted)) {
                    return json_decode($decrypted, true) ?? $decrypted;
                }

                return $decrypted;
            }

            if ($this->isJsonField($key)) {
                $decoded = json_decode($value, true);
                return $decoded !== null ? $decoded : $value;
            }
        }

        return $value;
    }

    /**
     * Resolve the user_id that "owns" this record for permission checking.
     *
     * The EncryptionService compares this against Auth::user()->id.
     * We must return the USER id, not the employee id.
     *
     * Override this method in any model that needs custom owner resolution.
     */
    protected function resolveOwnerId(): ?int
    {
        $class = get_class($this);

        // User model — the record IS the user
        if ($class === 'App\Models\User') {
            return isset($this->attributes['id']) ? (int) $this->attributes['id'] : null;
        }

        // Payslip model — owner is the user linked to the employee
        // We resolve lazily: check loaded relation first, then query
        if ($class === 'App\Models\Payslip') {
            // If employee relation is already loaded, use it
            if ($this->relationLoaded('employee') && $this->employee) {
                return isset($this->employee->attributes['user_id'])
                    ? (int) $this->employee->attributes['user_id']
                    : null;
            }
            // Fall back to a lightweight query — only runs once per payslip instance
            $employeeUserId = \DB::table('employees')
                ->where('id', $this->attributes['employee_id'] ?? 0)
                ->value('user_id');
            return $employeeUserId ? (int) $employeeUserId : null;
        }

        // All other models — use user_id column if present
        if (isset($this->attributes['user_id'])) {
            return (int) $this->attributes['user_id'];
        }

        return null;
    }

    /**
     * Return the raw ciphertext without decrypting.
     * Use only for internal / system operations.
     */
    public function getRawEncrypted(string $field): ?string
    {
        $value = $this->attributes[$field] ?? null;
        return is_array($value) ? null : $value;
    }

    /**
     * Force-decrypt bypassing all role checks.
     * Use only in trusted server-side contexts (PDF generation, exports, commands).
     */
    public function getForcedDecrypted(string $field): mixed
    {
        $value = $this->attributes[$field] ?? null;

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $service = app(EncryptionService::class);

            if ($service->isEncrypted($value)) {
                $decrypted = $service->decryptRaw($value);

                if ($this->isJsonField($field) && is_string($decrypted)) {
                    return json_decode($decrypted, true) ?? $decrypted;
                }

                return $decrypted;
            }

            if ($this->isJsonField($field)) {
                $decoded = json_decode($value, true);
                return $decoded !== null ? $decoded : $value;
            }
        }

        return $value;
    }

    public function canDecryptField(string $field): bool
    {
        return app(EncryptionService::class)->canDecrypt($field, $this->resolveOwnerId());
    }

    protected function isJsonField(string $field): bool
    {
        $casts = $this->getCasts();
        return isset($casts[$field]) && in_array($casts[$field], ['array', 'json'], true);
    }

    abstract public function getEncryptedFields(): array;
}