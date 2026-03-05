<?php

namespace App\Traits;

use App\Services\EncryptionService;
use Illuminate\Support\Facades\Log;

trait HasEncryptedFields
{
    /**
     * Boot the trait - auto-encrypt on save
     */
    public static function bootHasEncryptedFields(): void
    {
        static::saving(function ($model) {
            $service = app(EncryptionService::class);
            
            foreach ($model->getEncryptedFields() as $field) {
                if (!isset($model->attributes[$field])) {
                    continue;
                }

                $value = $model->attributes[$field];
                
                // Handle JSON fields - encode to string before encryption
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                
                // Encrypt if it's not already encrypted
                if ($value !== null && $value !== '' && !$service->isEncrypted($value)) {
                    try {
                        $model->attributes[$field] = $service->encrypt($value);
                    } catch (\Exception $e) {
                        Log::error("Failed to encrypt field {$field}", [
                            'model' => get_class($model),
                            'error' => $e->getMessage()
                        ]);
                        throw $e;
                    }
                }
            }
        });
    }

    /**
     * Override getAttribute to auto-decrypt with role checking
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (in_array($key, $this->getEncryptedFields(), true)) {
            $ownerId = $this->getOwnerId();
            
            // If value is already an array (from casting), it's not encrypted
            if (is_array($value)) {
                return $value;
            }
            
            // Handle JSON fields that might be stored as encrypted strings
            if (is_string($value)) {
                $service = app(EncryptionService::class);
                
                // Check if it's encrypted
                if ($service->isEncrypted($value)) {
                    $decrypted = $service->decrypt($value, $key, $ownerId);
                    
                    // If this is a JSON field, decode it
                    if ($this->isJsonField($key) && $decrypted && is_string($decrypted)) {
                        return json_decode($decrypted, true) ?: $decrypted;
                    }
                    
                    return $decrypted;
                }
                
                // Not encrypted, but might be JSON string
                if ($this->isJsonField($key)) {
                    $decoded = json_decode($value, true);
                    return $decoded !== null ? $decoded : $value;
                }
            }
            
            return $value;
        }

        return $value;
    }

    /**
     * Set attribute with auto-encryption
     */
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->getEncryptedFields(), true)) {
            $service = app(EncryptionService::class);
            
            // Handle JSON fields - encode to string before encryption
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }
            
            // Only encrypt if it's a string and not already encrypted
            if (is_string($value) && $value !== '' && !$service->isEncrypted($value)) {
                $this->attributes[$key] = $service->encrypt($value);
                return $this;
            }
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Get raw encrypted value (no decryption)
     */
    public function getRawEncrypted(string $field): ?string
    {
        $value = $this->attributes[$field] ?? null;
        
        // If it's an array, it's not encrypted
        if (is_array($value)) {
            return null;
        }
        
        return $value;
    }

    /**
     * Force decrypt (system use only - for exports, payroll processing)
     */
    public function getForcedDecrypted(string $field): mixed
    {
        $value = $this->attributes[$field] ?? null;
        
        // If it's already an array, return as is
        if (is_array($value)) {
            return $value;
        }
        
        // If it's a string, try to decrypt
        if (is_string($value)) {
            $service = app(EncryptionService::class);
            if ($service->isEncrypted($value)) {
                $decrypted = $service->decryptRaw($value);
                
                // Handle JSON fields
                if ($this->isJsonField($field) && $decrypted && is_string($decrypted)) {
                    return json_decode($decrypted, true) ?: $decrypted;
                }
                
                return $decrypted;
            }
            
            // Not encrypted, but might be JSON
            if ($this->isJsonField($field)) {
                $decoded = json_decode($value, true);
                return $decoded !== null ? $decoded : $value;
            }
        }
        
        return $value;
    }

    /**
     * Check if current user can decrypt a field
     */
    public function canDecryptField(string $field): bool
    {
        return app(EncryptionService::class)->canDecrypt($field, $this->getOwnerId());
    }

    /**
     * Get the owner ID for this record (usually user_id)
     */
    protected function getOwnerId(): ?int
    {
        if (isset($this->user_id)) {
            return (int) $this->user_id;
        }

        if (isset($this->id) && get_class($this) === 'App\Models\User') {
            return (int) $this->id;
        }

        return null;
    }

    /**
     * Check if field should be treated as JSON
     */
    protected function isJsonField(string $field): bool
    {
        $casts = $this->getCasts();
        return isset($casts[$field]) && in_array($casts[$field], ['array', 'json']);
    }

    /**
     * Define which fields should be encrypted
     */
    abstract public function getEncryptedFields(): array;
}