<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\TaxConfiguration;
use App\Models\SystemSetting;
use App\Models\Business;
use App\Services\LeaveBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Country;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    private const SETTINGS_FILE = 'settings.json';
    public function __construct(private LeaveBalanceService $leaveBalanceService)
    {
    }

        public function systemStats(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        
        // Build base queries with business scoping
        $employeeQuery = \App\Models\Employee::query();
        $managerQuery = \App\Models\User::where('role', 'manager');
        $payrollQuery = \App\Models\Payroll::where('status', 'completed');
        $leaveQuery = \App\Models\Leave::where('status', 'pending');
        $attendanceQuery = \App\Models\Attendance::query();
        
        // Apply business scoping if admin has business
        if ($currentUser->role === 'admin' && $currentUser->current_business_id) {
            $businessId = $currentUser->current_business_id;
            
            // Scope employees to business
            $employeeQuery->whereHas('user', function($q) use ($businessId) {
                $q->where('current_business_id', $businessId);
            });
            
            // Scope managers to business
            $managerQuery->where('current_business_id', $businessId);
            
            // Scope payrolls to business employees
            $payrollQuery->whereHas('employee.user', function($q) use ($businessId) {
                $q->where('current_business_id', $businessId);
            });
            
            // Scope leaves to business employees
            $leaveQuery->whereHas('employee.user', function($q) use ($businessId) {
                $q->where('current_business_id', $businessId);
            });
            
            // Scope attendance to business employees
            $attendanceQuery->whereHas('employee.user', function($q) use ($businessId) {
                $q->where('current_business_id', $businessId);
            });
            
            Log::info('ADMIN_CONTROLLER: Stats scoped to business', [
                'business_id' => $businessId,
                'admin_id' => $currentUser->id
            ]);
        } 
        // If admin without business, show only non-business data
        elseif ($currentUser->role === 'admin' && !$currentUser->current_business_id) {
            $employeeQuery->whereHas('user', function($q) {
                $q->whereNull('current_business_id');
            });
            
            $managerQuery->whereNull('current_business_id');
            
            $payrollQuery->whereHas('employee.user', function($q) {
                $q->whereNull('current_business_id');
            });
            
            $leaveQuery->whereHas('employee.user', function($q) {
                $q->whereNull('current_business_id');
            });
            
            $attendanceQuery->whereHas('employee.user', function($q) {
                $q->whereNull('current_business_id');
            });
            
            Log::info('ADMIN_CONTROLLER: Stats scoped to non-business data', [
                'admin_id' => $currentUser->id
            ]);
        }
        
        $stats = [
            'total_employees' => $employeeQuery->count(),
            'total_managers' => $managerQuery->count(),
            'total_payrolls_processed' => $payrollQuery->count(),
            'pending_leave_requests' => $leaveQuery->count(),
            'total_attendance_records' => $attendanceQuery->count(),
            'system_uptime' => $this->getSystemUptime(),
            'business_id' => $currentUser->current_business_id,
            'business_name' => $currentUser->current_business_id 
                ? $currentUser->currentBusiness->name ?? 'Unknown' 
                : 'No Business',
        ];
        
        return response()->json(['stats' => $stats]);
    }
      /**
     * Get active tax configuration
     */
        public function getTaxConfiguration(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        $countryCode = $request->query('country_code');
        
        // Determine country code
        if (!$countryCode) {
            // Try to get from user's business
            if ($currentUser->current_business_id) {
                $business = $currentUser->currentBusiness;
                $countryCode = $business->country_code ?? 'ZM'; // Default to Zambia
            } else {
                $countryCode = 'ZM'; // Default fallback
            }
        }
        
        Log::info('ADMIN_CONTROLLER: Getting tax config', [
            'user_id' => $currentUser->id,
            'business_id' => $currentUser->current_business_id,
            'country_code' => $countryCode
        ]);
        
        // Get appropriate tax configuration
        $taxConfig = TaxConfiguration::getForBusinessAndCountry(
            $currentUser->current_business_id,
            $countryCode
        );
        
        if (!$taxConfig) {
            Log::warning('ADMIN_CONTROLLER: No tax config found', [
                'business_id' => $currentUser->current_business_id,
                'country_code' => $countryCode
            ]);
            
            return response()->json([
                'tax_configuration' => null,
                'message' => 'No tax configuration found for this country'
            ], 404);
        }
        
        Log::info('ADMIN_CONTROLLER: Tax config loaded', [
            'tax_config_id' => $taxConfig->id,
            'country_code' => $taxConfig->country_code,
            'business_id' => $taxConfig->business_id,
            'scope' => $taxConfig->business_id ? 'business-specific' : 'country-default'
        ]);
        
        return response()->json([
            'tax_configuration' => array_merge($taxConfig->toArray(), [
                'scope' => $taxConfig->business_id ? 'business-specific' : 'country-default',
                'country_name' => $taxConfig->country->name ?? $taxConfig->country_name
            ])
        ]);
    }

    /**
     * Get all tax configurations for a specific country
     */
    public function getTaxConfigurationsByCountry(Request $request, string $countryCode): JsonResponse
    {
        $currentUser = $request->user();
        
        Log::info('ADMIN_CONTROLLER: Getting all tax configs for country', [
            'user_id' => $currentUser->id,
            'country_code' => $countryCode
        ]);
        
        $configs = TaxConfiguration::where('country_code', $countryCode)
            ->with(['business', 'country'])
            ->orderBy('business_id')
            ->orderByDesc('is_active')
            ->get()
            ->map(function ($config) {
                return array_merge($config->toArray(), [
                    'scope' => $config->business_id ? 'business-specific' : 'country-default',
                    'business_name' => $config->business->name ?? null,
                    'country_name' => $config->country->name ?? $config->country_name
                ]);
            });
        
        return response()->json(['tax_configurations' => $configs]);
    }

   /**
     * Save or update tax configuration
     */
    public function updateTaxConfiguration(Request $request): JsonResponse
    {
        // 1. LOG THE INCOMING DATA (For Debugging)
        \Log::info('Tax Update Request Payload:', $request->all());

        try {
            // 2. NEW VALIDATION RULES (Dynamic Statutory Deductions)
            $validated = $request->validate([
                'taxConfig' => 'required|array',
                'taxConfig.taxYear' => 'required|string',
                'taxConfig.currency' => 'required|string|size:3',
                
                // Allow these to be nullable if your frontend sends them as null
                'taxConfig.taxFreeThreshold' => 'nullable|numeric|min:0',
                'taxConfig.annualTaxFree' => 'nullable|numeric|min:0',
                
                // Tax Bands
                'taxConfig.taxBands' => 'required|array|min:1',
                'taxConfig.taxBands.*.lowerLimit' => 'required|numeric|min:0',
                'taxConfig.taxBands.*.upperLimit' => 'nullable|numeric|min:0',
                'taxConfig.taxBands.*.rate' => 'required|numeric|min:0|max:100',

                // *** CRITICAL CHANGE: Dynamic Deductions ***
                // We REMOVED nhimaEmployeeRate, napsaRate, etc.
                'taxConfig.statutory_deductions' => 'nullable|array',
                'taxConfig.statutory_deductions.*.name' => 'required|string|max:100',
                'taxConfig.statutory_deductions.*.type' => 'required|string',
                'taxConfig.statutory_deductions.*.base' => 'required|string|in:basic,gross',
                'taxConfig.statutory_deductions.*.employee_rate' => 'required|numeric|min:0',
                'taxConfig.statutory_deductions.*.employer_rate' => 'required|numeric|min:0',
                'taxConfig.statutory_deductions.*.ceiling' => 'nullable|numeric',

                // General Flags
                'taxConfig.taxCalculationMethod' => 'required|string',
                'taxConfig.roundingMethod' => 'required|string',
                'taxConfig.includeHousingAllowance' => 'boolean',
                'taxConfig.includeTransportAllowance' => 'boolean',
                
                // Scope
                'country_code' => 'nullable|string',
                'apply_to_business' => 'boolean',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log the validation errors if it fails
            \Log::error('Tax Update Validation Failed:', $e->errors());
            throw $e;
        }

        $currentUser = $request->user();
        $taxConfigData = $validated['taxConfig'];
        
        // Ensure annual equivalent is set
        if (isset($taxConfigData['taxFreeThreshold'])) {
            $taxConfigData['annualTaxFree'] = $taxConfigData['taxFreeThreshold'] * 12;
        }

        $countryCode = $validated['country_code'] ?? null;
        $applyToBusiness = $validated['apply_to_business'] ?? false;
        
        // Resolve Scope Names
        $countryName = 'Global';
        $country = null;
        
        if ($countryCode) {
            $country = \App\Models\Country::where('code', $countryCode)->first();
            $countryName = $country ? $country->name : $countryCode;
        }

        $businessId = null;
        $scopeName = $countryName;
        
        if ($applyToBusiness && $currentUser->current_business_id && $countryCode) {
            $businessId = $currentUser->current_business_id;
            $business = $currentUser->currentBusiness;
            $scopeName = $business ? "{$business->name} ({$countryName})" : "{$countryName} - Business #{$businessId}";
        } elseif ($countryCode === null) {
             $scopeName = "Global Fallback";
        }

        try {
            DB::beginTransaction();

            // Create/Update Logic
            $taxConfig = TaxConfiguration::create([
                'business_id' => $businessId,
                'country_code' => $countryCode,
                'country_name' => $countryName,
                'state' => null,
                'config_data' => $taxConfigData, // This saves the array structure exactly as received
                'is_active' => true,
            ]);

            // Update Business Link
            if ($businessId && isset($business)) {
                $business->update(['tax_configuration_id' => $taxConfig->id]);
            }

            // Deactivate old configs in this scope
            $query = TaxConfiguration::where('id', '!=', $taxConfig->id);
            
            if ($businessId) {
                $query->where('business_id', $businessId)->where('country_code', $countryCode);
            } elseif ($countryCode) {
                $query->whereNull('business_id')->where('country_code', $countryCode);
            } else {
                $query->whereNull('business_id')->whereNull('country_code');
            }
            $query->update(['is_active' => false]);

            // Clear Cache
            Cache::forget('tax_configuration_active');
            if ($countryCode) Cache::forget("tax_config_{$countryCode}");
            if ($businessId) Cache::forget("tax_config_business_{$businessId}_{$countryCode}");

            // Log Audit
            AuditLog::log(
                'UPDATE_TAX_CONFIG',
                "Tax configuration updated for {$scopeName}",
                ['config_id' => $taxConfig->id, 'scope' => $scopeName],
                auth()->id()
            );

            DB::commit();

            return response()->json([
                'tax_configuration' => array_merge($taxConfig->toArray(), [
                    'scope' => $businessId ? 'business-specific' : ($countryCode ? 'country-default' : 'global'),
                    'country_name' => $countryName
                ]),
                'message' => "Configuration saved successfully"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error saving tax config: ' . $e->getMessage());
            return response()->json(['message' => 'Save failed', 'error' => $e->getMessage()], 500);
        }
    }
    /**
     * Delete a tax configuration
     */
    public function deleteTaxConfiguration(Request $request, int $id): JsonResponse
    {
        $currentUser = $request->user();
        
        try {
            $taxConfig = TaxConfiguration::findOrFail($id);
            
            // Check permissions
            if ($taxConfig->business_id && $taxConfig->business_id !== $currentUser->current_business_id) {
                return response()->json([
                    'message' => 'You do not have permission to delete this tax configuration'
                ], 403);
            }
            
            $scopeName = $taxConfig->business_id 
                ? "{$taxConfig->business->name} ({$taxConfig->country->name})"
                : $taxConfig->country->name;
            
            $taxConfig->delete();
            
            // Clear caches
            Cache::forget('tax_configuration_active');
            Cache::forget("tax_config_{$taxConfig->country_code}");
            if ($taxConfig->business_id) {
                Cache::forget("tax_config_business_{$taxConfig->business_id}_{$taxConfig->country_code}");
            }
            
            // Log audit
            AuditLog::log(
                'DELETE_TAX_CONFIG',
                "Tax configuration deleted for {$scopeName}",
                [
                    'tax_config_id' => $id,
                    'country_code' => $taxConfig->country_code,
                    'business_id' => $taxConfig->business_id
                ],
                auth()->id()
            );
            
            return response()->json([
                'message' => "Tax configuration deleted successfully for {$scopeName}"
            ]);
        } catch (\Exception $e) {
            Log::error('ADMIN_CONTROLLER: Error deleting tax config', [
                'error' => $e->getMessage(),
                'tax_config_id' => $id
            ]);
            
            return response()->json([
                'message' => 'Failed to delete tax configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }
   
       public function auditLogs(Request $request): AnonymousResourceCollection
    {
        $currentUser = $request->user();
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');
        
        // If admin has business, show only logs related to their business
        if ($currentUser->current_business_id) {
            $query->where(function($q) use ($currentUser) {
                // Show logs by users in same business
                $q->whereHas('user', function($subQ) use ($currentUser) {
                    $subQ->where('current_business_id', $currentUser->current_business_id);
                })
                // Or logs without user (system logs) that mention the business
                ->orWhere('metadata->business_id', $currentUser->current_business_id);
            });
            
            Log::info('ADMIN_CONTROLLER: Audit logs filtered by business', [
                'business_id' => $currentUser->current_business_id
            ]);
        }
        
        $logs = $query->paginate(20);
        return \App\Http\Resources\AuditLogResource::collection($logs);
    }
   
     public function getCountries(Request $request): JsonResponse
    {
        Log::info('ADMIN_CONTROLLER: getCountries called', [
            'user_id' => auth()->id(),
            'timestamp' => now()->toDateTimeString()
        ]);
        
        try {
            // Get distinct country codes that have settings
            $countryCodes = SystemSetting::whereNotNull('country_code')
                ->where('country_code', '!=', 'global')
                ->distinct()
                ->pluck('country_code');
            
            Log::info('ADMIN_CONTROLLER: Country codes with settings', [
                'country_codes' => $countryCodes->toArray(),
                'count' => $countryCodes->count()
            ]);
            
            // Get country details for those codes
            $countries = Country::whereIn('code', $countryCodes)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function ($country) {
                    return [
                        'code' => $country->code,
                        'name' => $country->name,
                        'flag_emoji' => $country->flag_emoji,
                        'currency' => $country->currency,
                        'currency_symbol' => $country->currency_symbol,
                        'timezone' => $country->timezone,
                        'date_format' => $country->date_format,
                        'default_annual_leave' => $country->default_annual_leave,
                        'default_sick_leave' => $country->default_sick_leave,
                        'default_maternity_leave' => $country->default_maternity_leave,
                        'default_paternity_leave' => $country->default_paternity_leave,
                    ];
                })
                ->toArray();
            
            Log::info('ADMIN_CONTROLLER: Countries with settings retrieved', [
                'count' => count($countries),
                'countries' => $countries
            ]);
            
            return response()->json($countries);
        } catch (\Exception $e) {
            Log::error('ADMIN_CONTROLLER: Error fetching countries with settings', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Error fetching countries',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get countries that have settings configured
     */
    public function getCountriesWithSettings(Request $request): JsonResponse
    {
        Log::info('ADMIN_CONTROLLER: getCountriesWithSettings called', [
            'user_id' => auth()->id(),
            'timestamp' => now()->toDateTimeString()
        ]);
        
        try {
            $countryCodes = SystemSetting::whereNotNull('country_code')
                ->where('country_code', '!=', 'global')
                ->distinct()
                ->pluck('country_code');
            
            Log::info('ADMIN_CONTROLLER: Country codes found', [
                'codes' => $countryCodes->toArray(),
                'count' => $countryCodes->count()
            ]);
            
            if ($countryCodes->isEmpty()) {
                Log::warning('ADMIN_CONTROLLER: No countries with settings found');
                return response()->json([]);
            }
            
            $countries = Country::whereIn('code', $countryCodes)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
            
            Log::info('ADMIN_CONTROLLER: Countries from database', [
                'count' => $countries->count(),
                'codes' => $countries->pluck('code')->toArray()
            ]);
            
            $result = $countries->map(function ($country) {
                return [
                    'code' => $country->code,
                    'name' => $country->name,
                    'flag_emoji' => $country->flag_emoji ?? '🏳️',
                    'currency' => $country->currency ?? 'ZMW',
                    'currency_symbol' => $country->currency_symbol ?? 'K',
                    'timezone' => $country->timezone ?? 'UTC',
                    'date_format' => $country->date_format ?? 'd/m/Y',
                    'default_annual_leave' => $country->default_annual_leave ?? 21,
                    'default_sick_leave' => $country->default_sick_leave ?? 7,
                    'default_maternity_leave' => $country->default_maternity_leave ?? 90,
                    'default_paternity_leave' => $country->default_paternity_leave ?? 14,
                ];
            })->toArray();
            
            Log::info('ADMIN_CONTROLLER: Mapped countries result', [
                'count' => count($result),
                'result' => $result
            ]);
            
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('ADMIN_CONTROLLER: Error in getCountriesWithSettings', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Error fetching countries with settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all available countries
     */
    public function getAllCountries(Request $request): JsonResponse
    {
        Log::info('ADMIN_CONTROLLER: getAllCountries called', [
            'user_id' => auth()->id(),
            'timestamp' => now()->toDateTimeString()
        ]);
        
        try {
            $countries = Country::where('is_active', true)
                ->where('code', '!=', 'global')
                ->orderBy('name')
                ->get();
            
            Log::info('ADMIN_CONTROLLER: All countries query result', [
                'count' => $countries->count(),
                'codes' => $countries->pluck('code')->toArray()
            ]);
            
            $result = $countries->map(function ($country) {
                $hasSettings = SystemSetting::where('country_code', $country->code)->exists();
                
                return [
                    'code' => $country->code,
                    'name' => $country->name,
                    'flag_emoji' => $country->flag_emoji ?? '🏳️',
                    'currency' => $country->currency ?? 'ZMW',
                    'currency_symbol' => $country->currency_symbol ?? 'K',
                    'timezone' => $country->timezone ?? 'UTC',
                    'date_format' => $country->date_format ?? 'd/m/Y',
                    'default_annual_leave' => $country->default_annual_leave ?? 21,
                    'default_sick_leave' => $country->default_sick_leave ?? 7,
                    'default_maternity_leave' => $country->default_maternity_leave ?? 90,
                    'default_paternity_leave' => $country->default_paternity_leave ?? 14,
                    'has_settings' => $hasSettings
                ];
            })->toArray();
            
            Log::info('ADMIN_CONTROLLER: All countries mapped result', [
                'count' => count($result),
                'result' => $result
            ]);
            
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('ADMIN_CONTROLLER: Error in getAllCountries', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Error fetching countries',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available countries
     */
    public function getAvailableCountries(Request $request): JsonResponse
    {
        Log::info('ADMIN_CONTROLLER: getAvailableCountries called', [
            'user_id' => auth()->id(),
            'timestamp' => now()->toDateTimeString()
        ]);
        
        try {
            $countries = Country::where('is_active', true)
                ->where('code', '!=', 'global')
                ->orderBy('name')
                ->get();
            
            Log::info('ADMIN_CONTROLLER: Available countries query', [
                'count' => $countries->count(),
                'table_exists' => DB::getSchemaBuilder()->hasTable('countries'),
                'table_columns' => DB::getSchemaBuilder()->hasTable('countries') 
                    ? DB::getSchemaBuilder()->getColumnListing('countries')
                    : []
            ]);
            
            $result = $countries->map(function ($country) {
                $hasSettings = SystemSetting::where('country_code', $country->code)->exists();
                
                return [
                    'code' => $country->code,
                    'name' => $country->name,
                    'flag_emoji' => $country->flag_emoji ?? '🏳️',
                    'currency' => $country->currency ?? 'ZMW',
                    'currency_symbol' => $country->currency_symbol ?? 'K',
                    'timezone' => $country->timezone ?? 'UTC',
                    'date_format' => $country->date_format ?? 'd/m/Y',
                    'default_annual_leave' => $country->default_annual_leave ?? 21,
                    'default_sick_leave' => $country->default_sick_leave ?? 7,
                    'default_maternity_leave' => $country->default_maternity_leave ?? 90,
                    'default_paternity_leave' => $country->default_paternity_leave ?? 14,
                    'has_settings' => $hasSettings
                ];
            })->toArray();
            
            Log::info('ADMIN_CONTROLLER: Available countries result', [
                'count' => count($result)
            ]);

            return response()->json(['countries' => $result]);
        } catch (\Exception $e) {
            Log::error('ADMIN_CONTROLLER: Error in getAvailableCountries', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Error fetching countries',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's accessible businesses with their countries
     */
    public function getBusinessesWithCountries(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        
        Log::info('ADMIN: Getting businesses with countries', [
            'user_id' => $currentUser->id,
            'role' => $currentUser->role,
            'current_business_id' => $currentUser->current_business_id
        ]);
        
        try {
            $query = Business::with('country')
                ->where('status', 'active');
            
            // Super admin and admin can see all businesses
            $isAdminRole = in_array($currentUser->role, ['super_admin', 'admin']);
            
            if (!$isAdminRole) {
                if ($currentUser->current_business_id) {
                    $query->where('id', $currentUser->current_business_id);
                } else {
                    Log::warning('ADMIN: Non-admin user with no business', [
                        'user_id' => $currentUser->id
                    ]);
                    return response()->json(['businesses' => []]);
                }
            }
            
            $businesses = $query->get()->map(function($business) {
                return [
                    'id' => $business->id,
                    'name' => $business->name,
                    'legal_name' => $business->legal_name,
                    'country_code' => $business->country_id ? ($business->country->code ?? null) : null,
                    'country_name' => $business->country->name ?? 'Unknown',
                    'flag_emoji' => $business->country->flag_emoji ?? '🏳️',
                    'currency_code' => $business->currency_code,
                    'tax_identification_number' => $business->tax_identification_number,
                    'has_settings' => $business->country_id ? SystemSetting::where('business_id', $business->id)
                        ->where('country_code', $business->country->code)
                        ->exists() : false
                ];
            });
            
            Log::info('ADMIN: Businesses retrieved', [
                'count' => $businesses->count(),
                'businesses' => $businesses->pluck('name')->toArray()
            ]);
            
            return response()->json(['businesses' => $businesses]);
        } catch (\Exception $e) {
            Log::error('ADMIN: Error getting businesses', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Error fetching businesses',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
 * Get settings for a specific business and country
 */
public function getSettings(Request $request): JsonResponse
{
    $currentUser = $request->user();
    $businessId = $request->query('business_id');
    $countryCode = $request->query('country_code');
    
    Log::info('ADMIN: getSettings called', [
        'user_id' => $currentUser->id,
        'user_role' => $currentUser->role,
        'requested_business_id' => $businessId,
        'user_current_business_id' => $currentUser->current_business_id,
        'requested_country_code' => $countryCode
    ]);
    
    // Determine business and country
    if (!$businessId && $currentUser->current_business_id) {
        $businessId = $currentUser->current_business_id;
        Log::info('ADMIN: Using user current business', ['business_id' => $businessId]);
    }
    
    if ($businessId) {
        $business = Business::with('country')->find($businessId);
        if (!$business) {
            Log::warning('ADMIN: Business not found', [
                'business_id' => $businessId
            ]);
            return response()->json([
                'message' => 'Business not found'
            ], 404);
        }
        
        // Check access - super_admin and admin roles can access
        $isSuperAdmin = in_array($currentUser->role, ['super_admin', 'admin']);
        $ownsBusinessOrAdmin = $isSuperAdmin || ($currentUser->current_business_id == $businessId);
        
        Log::info('ADMIN: Access check', [
            'user_role' => $currentUser->role,
            'is_super_admin' => $isSuperAdmin,
            'owns_business' => $currentUser->current_business_id == $businessId,
            'access_granted' => $ownsBusinessOrAdmin
        ]);
        
        if (!$ownsBusinessOrAdmin) {
            Log::warning('ADMIN: Access denied', [
                'user_id' => $currentUser->id,
                'user_role' => $currentUser->role,
                'business_id' => $businessId
            ]);
            return response()->json([
                'message' => 'You do not have access to this business'
            ], 403);
        }
        
        // Get country code from business if not provided
        if (!$countryCode && $business->country) {
            $countryCode = $business->country->code;
            Log::info('ADMIN: Using business country', ['country_code' => $countryCode]);
        }
    }
    
    Log::info('ADMIN: Getting settings', [
        'user_id' => $currentUser->id,
        'business_id' => $businessId,
        'country_code' => $countryCode
    ]);
    
    try {
        $cacheKey = $businessId 
            ? "system_settings_{$businessId}_{$countryCode}" 
            : "system_settings_{$countryCode}";
        
        $settings = Cache::remember($cacheKey, 300, function () use ($businessId, $countryCode) {
            return SystemSetting::getAllSettings($businessId, $countryCode);
        });
        
        // Add context information
        $response = [
            'settings' => $settings,
            'business_id' => $businessId,
            'country_code' => $countryCode
        ];
        
        if ($businessId) {
            $business = Business::with('country')->find($businessId);
            $response['business_info'] = [
                'id' => $business->id,
                'name' => $business->name,
                'legal_name' => $business->legal_name,
                'country_code' => $business->country->code ?? null,
                'country_name' => $business->country->name ?? 'Unknown',
                'flag_emoji' => $business->country->flag_emoji ?? '🏳️',
                'currency_code' => $business->currency_code,
                'tax_identification_number' => $business->tax_identification_number
            ];
        }
        
        if ($countryCode) {
            $country = Country::where('code', $countryCode)->first();
            if ($country) {
                $response['country_info'] = [
                    'code' => $country->code,
                    'name' => $country->name,
                    'flag_emoji' => $country->flag_emoji,
                    'currency' => $country->currency,
                    'currency_symbol' => $country->currency_symbol,
                    'timezone' => $country->timezone,
                    'date_format' => $country->date_format
                ];
            }
        }
        
        Log::info('ADMIN: Settings retrieved successfully', [
            'business_id' => $businessId,
            'country_code' => $countryCode,
            'settings_count' => count($settings)
        ]);
        
        return response()->json($response);
    } catch (\Exception $e) {
        Log::error('ADMIN: Error fetching settings', [
            'error' => $e->getMessage(),
            'business_id' => $businessId,
            'country_code' => $countryCode,
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'message' => 'Error fetching settings',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Initialize settings for a business
 */
public function initializeBusinessSettings(Request $request): JsonResponse
{
    $validated = $request->validate([
        'business_id' => 'required|exists:businesses,id',
        'company_name' => 'required|string|max:255',
        'company_address' => 'nullable|string|max:500',
        'tax_id' => 'nullable|string|max:50',
        'currency' => 'required|string|in:USD,ZMW,EUR,GBP,ZAR',
        'annual_leave_days' => 'required|integer|min:0|max:365',
        'sick_leave_days' => 'required|integer|min:0|max:365',
        'maternity_leave_days' => 'required|integer|min:0|max:365',
        'paternity_leave_days' => 'required|integer|min:0|max:365',
        'date_format' => 'required|string|in:d/m/Y,m/d/Y,Y-m-d,d M Y',
        'default_password' => 'required|string|min:6',
        'max_login_attempts' => 'required|integer|min:1|max:10',
        'session_timeout' => 'required|integer|min:1|max:480',
        'departments' => 'required|array',
        'departments.*.name' => 'required|string|max:100'
    ]);

    $currentUser = $request->user();
    $businessId = $validated['business_id'];
    
    // Check access - super_admin and admin can access
    $isSuperAdmin = in_array($currentUser->role, ['super_admin', 'admin']);
    if (!$isSuperAdmin && $currentUser->current_business_id != $businessId) {
        return response()->json([
            'message' => 'You do not have access to this business'
        ], 403);
    }
    
    $business = Business::with('country')->findOrFail($businessId);
    $countryCode = $business->country->code ?? 'ZM';
    
    Log::info('ADMIN: Initializing business settings', [
        'business_id' => $businessId,
        'country_code' => $countryCode
    ]);

    try {
        DB::beginTransaction();
        
        // Check if settings already exist
        $existingSettings = SystemSetting::where('business_id', $businessId)
            ->where('country_code', $countryCode)
            ->exists();
        
        if ($existingSettings) {
            return response()->json([
                'message' => 'Settings already exist for this business',
                'error' => 'Business settings already initialized'
            ], 409);
        }

        // Create settings
        foreach ($validated as $key => $value) {
            if ($key === 'business_id') continue;
            
            SystemSetting::setSetting($key, $value, $businessId, $countryCode);
        }

        DB::commit();
        
        // Clear cache
        Cache::forget("system_settings_{$businessId}_{$countryCode}");
        
        // Log audit
        AuditLog::log(
            'INITIALIZE_BUSINESS_SETTINGS',
            "Settings initialized for {$business->name}",
            [
                'business_id' => $businessId,
                'country_code' => $countryCode
            ],
            auth()->id()
        );

        return response()->json([
            'message' => "Settings initialized successfully for {$business->name}",
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'country_code' => $countryCode
            ]
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('ADMIN: Error initializing business settings', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'message' => 'Failed to initialize settings',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Update settings for a business
 */
public function updateSettings(Request $request): JsonResponse
{
    $validated = $request->validate([
        'business_id' => 'required|exists:businesses,id',
        'company_name' => 'required|string|max:255',
        'company_address' => 'nullable|string|max:500',
        'tax_id' => 'nullable|string|max:50',
        'currency' => 'required|string|in:USD,ZMW,EUR,GBP,ZAR',
        'annual_leave_days' => 'required|integer|min:0|max:365',
        'sick_leave_days' => 'required|integer|min:0|max:365',
        'maternity_leave_days' => 'required|integer|min:0|max:365',
        'paternity_leave_days' => 'required|integer|min:0|max:365',
        'default_password' => 'required|string|min:6',
        'max_login_attempts' => 'required|integer|min:1|max:10',
        'session_timeout' => 'required|integer|min:1|max:480',
        'date_format' => 'required|string|in:d/m/Y,m/d/Y,Y-m-d,d M Y',
        'departments' => 'required|array',
        'departments.*.name' => 'required|string|max:100'
    ]);

    $currentUser = $request->user();
    $businessId = $validated['business_id'];
    
    Log::info('ADMIN: updateSettings called', [
        'user_id' => $currentUser->id,
        'user_role' => $currentUser->role,
        'business_id' => $businessId,
        'user_current_business_id' => $currentUser->current_business_id
    ]);
    
    // Check access - super_admin and admin can access
    $isSuperAdmin = in_array($currentUser->role, ['super_admin', 'admin']);
    if (!$isSuperAdmin && $currentUser->current_business_id != $businessId) {
        Log::warning('ADMIN: Access denied for updateSettings', [
            'user_id' => $currentUser->id,
            'user_role' => $currentUser->role,
            'business_id' => $businessId
        ]);
        return response()->json([
            'message' => 'You do not have access to this business'
        ], 403);
    }
    
    $business = Business::with('country')->findOrFail($businessId);
    $countryCode = $business->country->code ?? 'ZM';

    try {
        DB::beginTransaction();
        
        // Track leave settings changes
        $leaveSettingsChanged = false;
        $leaveKeys = ['annual_leave_days', 'sick_leave_days', 'maternity_leave_days', 'paternity_leave_days'];
        
        foreach ($leaveKeys as $key) {
            $currentValue = SystemSetting::getSetting($key, $businessId, $countryCode);
            if ($currentValue != $validated[$key]) {
                $leaveSettingsChanged = true;
                Log::info('ADMIN: Leave setting changed', [
                    'key' => $key,
                    'old_value' => $currentValue,
                    'new_value' => $validated[$key]
                ]);
                break;
            }
        }

        // Update settings
        foreach ($validated as $key => $value) {
            if ($key === 'business_id') continue;
            
            SystemSetting::setSetting($key, $value, $businessId, $countryCode);
        }

        DB::commit();

        // Clear cache
        Cache::forget("system_settings_{$businessId}_{$countryCode}");

        // Update leave balances if needed
        $updatedCount = 0;
        if ($leaveSettingsChanged) {
            $newLeaveSettings = array_intersect_key($validated, array_flip($leaveKeys));
            Log::info('ADMIN: Syncing leave balances', [
                'business_id' => $businessId,
                'new_settings' => $newLeaveSettings
            ]);
            
            $updatedCount = $this->leaveBalanceService->syncEmployeeBalancesForBusiness(
                $businessId,
                $newLeaveSettings
            );
        }

        // Log audit
        AuditLog::log(
            'UPDATE_BUSINESS_SETTINGS',
            "Settings updated for {$business->name}" . 
            ($leaveSettingsChanged ? " (synced {$updatedCount} leave balances)" : ''),
            [
                'business_id' => $businessId,
                'updated_fields' => array_keys($validated),
                'leave_settings_changed' => $leaveSettingsChanged,
                'balances_updated' => $updatedCount
            ],
            auth()->id()
        );

        $message = 'Settings updated successfully';
        if ($leaveSettingsChanged) {
            $employeeCount = $updatedCount / count($leaveKeys);
            $message .= ". Leave balances for {$employeeCount} employees have been adjusted.";
        }

        return response()->json([
            'message' => $message,
            'settings' => $validated,
            'leave_balances_updated' => $leaveSettingsChanged,
            'updated_count' => $updatedCount
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('ADMIN: Error updating settings', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'message' => 'Failed to update settings',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Delete business settings
 */
public function deleteBusinessSettings(Request $request, int $businessId): JsonResponse
{
    $currentUser = $request->user();
    
    Log::info('ADMIN: deleteBusinessSettings called', [
        'user_id' => $currentUser->id,
        'user_role' => $currentUser->role,
        'business_id' => $businessId,
        'user_current_business_id' => $currentUser->current_business_id
    ]);
    
    // Check access - super_admin and admin can access
    $isSuperAdmin = in_array($currentUser->role, ['super_admin', 'admin']);
    if (!$isSuperAdmin && $currentUser->current_business_id != $businessId) {
        Log::warning('ADMIN: Access denied for deleteBusinessSettings', [
            'user_id' => $currentUser->id,
            'user_role' => $currentUser->role,
            'business_id' => $businessId
        ]);
        return response()->json([
            'message' => 'You do not have access to this business'
        ], 403);
    }
    
    try {
        $business = Business::with('country')->findOrFail($businessId);
        $countryCode = $business->country->code ?? 'ZM';
        
        $deletedCount = SystemSetting::where('business_id', $businessId)
            ->where('country_code', $countryCode)
            ->delete();

        Cache::forget("system_settings_{$businessId}_{$countryCode}");

        AuditLog::log(
            'DELETE_BUSINESS_SETTINGS',
            "Settings deleted for {$business->name}",
            [
                'business_id' => $businessId,
                'deleted_count' => $deletedCount
            ],
            auth()->id()
        );

        return response()->json([
            'message' => "Settings deleted successfully for {$business->name}",
            'deleted_count' => $deletedCount
        ]);
    } catch (\Exception $e) {
        Log::error('ADMIN: Error deleting business settings', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'message' => 'Failed to delete settings',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Initialize settings for a new country
 */
public function initializeCountrySettings(Request $request): JsonResponse
{
    Log::info('ADMIN_CONTROLLER: initializeCountrySettings called', [
        'user_id' => auth()->id(),
        'request_data' => $request->all()
    ]);
    
    $validated = $request->validate([
        'country_code' => 'required|string|max:10|exists:countries,code',
        'company_name' => 'required|string|max:255',
        'company_address' => 'nullable|string|max:500',
        'tax_id' => 'nullable|string|max:50',
        'currency' => 'required|string|in:USD,ZMW,EUR,GBP,ZAR',
        'annual_leave_days' => 'required|integer|min:0|max:365',
        'sick_leave_days' => 'required|integer|min:0|max:365',
        'maternity_leave_days' => 'required|integer|min:0|max:365',
        'paternity_leave_days' => 'required|integer|min:0|max:365',
        'date_format' => 'required|string|in:d/m/Y,m/d/Y,Y-m-d,d M Y',
        'default_password' => 'required|string|min:6',
        'max_login_attempts' => 'required|integer|min:1|max:10',
        'session_timeout' => 'required|integer|min:1|max:480',
        'departments' => 'required|array',
        'departments.*.name' => 'required|string|max:100'
    ]);

    try {
        DB::beginTransaction();
        
        $countryCode = $validated['country_code'];
        
        Log::info('ADMIN_CONTROLLER: Checking for existing settings', [
            'country_code' => $countryCode
        ]);
        
        // Check if settings already exist for this country
        $existingSettings = SystemSetting::where('country_code', $countryCode)
            ->whereNull('business_id')
            ->exists();
            
        if ($existingSettings) {
            Log::warning('ADMIN_CONTROLLER: Settings already exist', [
                'country_code' => $countryCode
            ]);
            
            return response()->json([
                'message' => 'Settings already exist for this country',
                'error' => 'Country settings already initialized'
            ], 409);
        }

        // Get country info
        $country = Country::where('code', $countryCode)->firstOrFail();
        
        Log::info('ADMIN_CONTROLLER: Country found', [
            'country_code' => $countryCode,
            'country_name' => $country->name
        ]);

        // Create settings
        foreach ($validated as $key => $value) {
            if ($key === 'country_code') continue;
            
            SystemSetting::setSetting($key, $value, null, $countryCode);
        }

        DB::commit();
        
        Log::info('ADMIN_CONTROLLER: Settings initialized successfully', [
            'country_code' => $countryCode,
            'country_name' => $country->name
        ]);

        // Clear cache
        Cache::forget("system_settings_{$countryCode}");

        // Log audit
        AuditLog::log(
            'INITIALIZE_COUNTRY_SETTINGS',
            "Country settings initialized for {$country->name}",
            [
                'country_code' => $countryCode,
                'country_name' => $country->name
            ],
            auth()->id()
        );

        return response()->json([
            'message' => "Settings initialized successfully for {$country->name}",
            'country' => [
                'code' => $country->code,
                'name' => $country->name,
                'flag_emoji' => $country->flag_emoji
            ]
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('ADMIN_CONTROLLER: Error initializing country settings', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'message' => 'Failed to initialize country settings',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Delete country settings
 */
public function deleteCountrySettings(Request $request, string $countryCode): JsonResponse
{
    try {
        // Don't allow deletion of global settings
        if ($countryCode === 'global') {
            return response()->json([
                'message' => 'Cannot delete global settings'
            ], 400);
        }

        // Check if country exists
        $country = Country::where('code', $countryCode)->first();
        if (!$country) {
            return response()->json([
                'message' => 'Country not found'
            ], 404);
        }

        // Delete all country settings (not business-specific)
        $deletedCount = SystemSetting::where('country_code', $countryCode)
            ->whereNull('business_id')
            ->delete();

        // Clear cache
        Cache::forget("system_settings_{$countryCode}");

        // Log audit
        AuditLog::log(
            'DELETE_COUNTRY_SETTINGS',
            "Country settings deleted for {$country->name}",
            [
                'country_code' => $countryCode,
                'country_name' => $country->name,
                'deleted_count' => $deletedCount
            ],
            auth()->id()
        );

        return response()->json([
            'message' => "Settings deleted successfully for {$country->name}",
            'deleted_count' => $deletedCount
        ]);
    } catch (\Exception $e) {
        Log::error('Error deleting country settings: ' . $e->getMessage());
        return response()->json([
            'message' => 'Failed to delete country settings',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Get system uptime
 */
private function getSystemUptime(): string
{
    try {
        if (function_exists('shell_exec')) {
            $uptime = shell_exec('uptime -p');
            return $uptime ? trim($uptime) : 'Unknown';
        }
        return 'Unknown';
    } catch (\Exception $e) {
        return 'Unknown';
    }
}
}