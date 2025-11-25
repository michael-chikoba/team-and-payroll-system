<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\TaxConfiguration;
use App\Models\SystemSetting;
use App\Services\LeaveBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
class AdminController extends Controller
{
    private const SETTINGS_FILE = 'settings.json';
    public function __construct(private LeaveBalanceService $leaveBalanceService)
    {
    }
      /**
     * Get active tax configuration
     */
    public function getTaxConfiguration(): JsonResponse
    {
        Cache::forget('tax_configuration_active');
        $taxConfig = TaxConfiguration::active()->first();

        // If no configuration exists, return empty object instead of default values
        if (!$taxConfig) {
            return response()->json(['tax_configuration' => null]);
        }

        return response()->json(['tax_configuration' => $taxConfig->toArray()]);
    }

    /**
     * Save or update tax configuration in DB
     */
    public function updateTaxConfiguration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'taxConfig' => 'required|array',
            'taxConfig.taxYear' => 'required|string|in:2024,2025,2026,2027,2028',
            'taxConfig.currency' => 'required|string|in:ZMW,USD',
            'taxConfig.taxFreeThreshold' => 'required|numeric|min:0',
            'taxConfig.annualTaxFree' => 'nullable|numeric|min:0',
            'taxConfig.taxBands' => 'required|array|min:2',
            'taxConfig.taxBands.*.lowerLimit' => 'required|numeric|min:0',
            'taxConfig.taxBands.*.upperLimit' => 'nullable|numeric|min:0',
            'taxConfig.taxBands.*.rate' => 'required|numeric|min:0|max:100',
            'taxConfig.nhimaEmployeeRate' => 'required|numeric|min:0|max:10',
            'taxConfig.nhimaEmployerRate' => 'required|numeric|min:0|max:10',
            'taxConfig.nhimaMaxSalary' => 'nullable|numeric|min:0',
            'taxConfig.napsaRate' => 'nullable|numeric|min:0|max:10',
            'taxConfig.napsaMaxSalary' => 'nullable|numeric|min:0',
            'taxConfig.payGrades' => 'nullable|array',
            'taxConfig.payGrades.*.grade' => 'nullable|string|max:5',
            'taxConfig.payGrades.*.name' => 'nullable|string|max:100',
            'taxConfig.payGrades.*.minSalary' => 'nullable|numeric|min:0',
            'taxConfig.payGrades.*.maxSalary' => 'nullable|numeric|min:0',
            'taxConfig.payGrades.*.description' => 'nullable|string',
            'taxConfig.includeHousingAllowance' => 'boolean',
            'taxConfig.includeTransportAllowance' => 'boolean',
            'taxConfig.taxCalculationMethod' => ['required', Rule::in(['cumulative', 'non_cumulative'])],
            'taxConfig.roundingMethod' => ['required', Rule::in(['nearest', 'up', 'down', 'none'])]
        ]);

        $taxConfigData = $validated['taxConfig'];
        $taxConfigData['annualTaxFree'] = $taxConfigData['taxFreeThreshold'] * 12;

        // Save to database
        $taxConfig = TaxConfiguration::updateOrCreate(
            ['country' => 'Zambia'],
            [
                'state' => null,
                'config_data' => $taxConfigData,
                'is_active' => true
            ]
        );

        // Deactivate other records
        TaxConfiguration::where('id', '!=', $taxConfig->id)->update(['is_active' => false]);

        // Log audit
        AuditLog::log(
            'UPDATE_TAX_CONFIG',
            'Tax configuration updated for Zambia ' . $taxConfigData['taxYear'],
            ['tax_config_id' => $taxConfig->id, 'tax_year' => $taxConfigData['taxYear']],
            auth()->id()
        );

        return response()->json([
            'tax_configuration' => $taxConfig->toArray(),
            'message' => 'Tax configuration saved successfully'
        ]);
    }

    /**
     * List audit logs
     */
    public function auditLogs(Request $request): AnonymousResourceCollection
    {
        $logs = AuditLog::with('user')->orderBy('created_at', 'desc')->paginate(20);
        return \App\Http\Resources\AuditLogResource::collection($logs);
    }
    public function getSettings(): JsonResponse
    {
        try {
            // Try to get from cache first (5 minutes)
            $settings = Cache::remember('system_settings_all', 300, function () {
                $settingsArray = [];
              
                $dbSettings = SystemSetting::all();
              
                foreach ($dbSettings as $setting) {
                    $key = $setting->key;
                    $value = $setting->value;
                  
                    // Parse JSON values (like departments)
                    if ($key === 'departments') {
                        $decoded = json_decode($value, true);
                        $settingsArray[$key] = is_array($decoded) ? $decoded : [];
                    } else {
                        // Convert numeric strings to integers for leave days
                        if (strpos($key, 'leave_days') !== false ||
                            in_array($key, ['max_login_attempts', 'session_timeout'])) {
                            $settingsArray[$key] = is_numeric($value) ? (int)$value : $value;
                        } else {
                            $settingsArray[$key] = $value;
                        }
                    }
                }
              
                return $settingsArray;
            });
            // Ensure all expected keys exist with defaults
            $defaults = [
                'company_name' => 'Payroll System',
                'company_address' => '',
                'tax_id' => '',
                'currency' => 'ZMW',
                'annual_leave_days' => 21,
                'sick_leave_days' => 7,
                'maternity_leave_days' => 90,
                'paternity_leave_days' => 14,
                'default_password' => 'Password123!',
                'max_login_attempts' => 5,
                'session_timeout' => 60,
                'departments' => [
                    ['name' => 'IT'],
                    ['name' => 'HR'],
                    ['name' => 'Finance'],
                    ['name' => 'Sales']
                ]
            ];
            $settings = array_merge($defaults, $settings);
            return response()->json($settings);
        } catch (\Exception $e) {
            Log::error('Error fetching settings: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_address' => 'nullable|string|max:500',
            'tax_id' => 'nullable|string|max:50',
            'currency' => 'required|string|in:USD,ZMW,EUR',
            'annual_leave_days' => 'required|integer|min:0|max:365',
            'sick_leave_days' => 'required|integer|min:0|max:365',
            'maternity_leave_days' => 'required|integer|min:0|max:365',
            'paternity_leave_days' => 'required|integer|min:0|max:365',
            'default_password' => 'required|string|min:6',
            'max_login_attempts' => 'required|integer|min:1|max:10',
            'session_timeout' => 'required|integer|min:1|max:480',
            'departments' => 'required|array',
            'departments.*.name' => 'required|string|max:100'
        ]);
        try {
            // Track if leave settings changed
            $leaveSettingsChanged = false;
            $oldLeaveSettings = [];
            $newLeaveSettings = [];
            // Check if leave-related settings changed
            $leaveKeys = ['annual_leave_days', 'sick_leave_days', 'maternity_leave_days', 'paternity_leave_days'];
          
            foreach ($leaveKeys as $key) {
                $currentSetting = SystemSetting::where('key', $key)->first();
                $currentValue = $currentSetting ? (int)$currentSetting->value : null;
                $newValue = (int)$validated[$key];
              
                if ($currentValue !== $newValue) {
                    $leaveSettingsChanged = true;
                    $oldLeaveSettings[$key] = $currentValue;
                    $newLeaveSettings[$key] = $newValue;
                }
            }
            // Update system settings in database
            foreach ($validated as $key => $value) {
                if ($key === 'departments') {
                    // Handle departments specially (store as JSON)
                    SystemSetting::updateOrCreate(
                        ['key' => 'departments'],
                        [
                            'value' => json_encode($value),
                            'description' => 'Available departments in the company'
                        ]
                    );
                } else {
                    // Get description based on key
                    $description = $this->getSettingDescription($key);
                  
                    SystemSetting::updateOrCreate(
                        ['key' => $key],
                        [
                            'value' => is_array($value) ? json_encode($value) : $value,
                            'description' => $description
                        ]
                    );
                }
            }
            // Clear all settings caches
            Cache::forget('system_settings_all');
            foreach ($leaveKeys as $key) {
                $type = str_replace('_leave_days', '', $key);
                Cache::forget("leave_allocation_{$type}");
            }
            // If leave settings changed, update all employee balances
            $updatedCount = 0;
            if ($leaveSettingsChanged) {
                Log::info('Leave settings changed, syncing employee balances', [
                    'old_settings' => $oldLeaveSettings,
                    'new_settings' => $newLeaveSettings,
                    'user_id' => auth()->id()
                ]);
              
                $updatedCount = $this->leaveBalanceService->syncAllEmployeeBalancesWithSettings();
              
                Log::info('Employee leave balances synced', [
                    'updated_count' => $updatedCount
                ]);
            }
            // Also update the JSON file for backward compatibility
            try {
                $settingsFile = storage_path('app/' . self::SETTINGS_FILE);
                $fileContent = json_encode($validated, JSON_PRETTY_PRINT);
                File::put($settingsFile, $fileContent);
            } catch (\Exception $e) {
                Log::warning('Failed to update settings JSON file: ' . $e->getMessage());
            }
            // Log the action
            AuditLog::log(
                'UPDATE_SYSTEM_SETTINGS',
                'System settings updated' . ($leaveSettingsChanged ? " (synced {$updatedCount} leave balances)" : ''),
                [
                    'updated_fields' => array_keys($validated),
                    'leave_settings_changed' => $leaveSettingsChanged,
                    'balances_updated' => $updatedCount
                ],
                auth()->id()
            );
            $message = 'Settings updated successfully';
            if ($leaveSettingsChanged) {
                $message .= ". Leave balances for {$updatedCount} employee records have been automatically adjusted.";
            }
            return response()->json([
                'message' => $message,
                'settings' => $validated,
                'leave_balances_updated' => $leaveSettingsChanged,
                'updated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating settings: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        
            return response()->json([
                'message' => 'Failed to update settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Get description for a setting key
     */
    private function getSettingDescription(string $key): string
    {
        $descriptions = [
            'company_name' => 'Company name for the system',
            'company_address' => 'Company physical address',
            'tax_id' => 'Company tax identification number',
            'currency' => 'Default currency for payroll',
            'annual_leave_days' => 'Default annual leave days per year',
            'sick_leave_days' => 'Default sick leave days per year',
            'maternity_leave_days' => 'Default maternity leave days',
            'paternity_leave_days' => 'Default paternity leave days',
            'default_password' => 'Default password for new employees',
            'max_login_attempts' => 'Maximum login attempts before lockout',
            'session_timeout' => 'Session timeout in minutes',
        ];
        return $descriptions[$key] ?? null;
    }
    public function systemStats(): JsonResponse
    {
        $stats = [
            'total_employees' => \App\Models\Employee::count(),
            'total_managers' => \App\Models\User::where('role', 'manager')->count(),
            'total_payrolls_processed' => \App\Models\Payroll::where('status', 'completed')->count(),
            'pending_leave_requests' => \App\Models\Leave::where('status', 'pending')->count(),
            'total_attendance_records' => \App\Models\Attendance::count(),
            'system_uptime' => $this->getSystemUptime(),
        ];
        return response()->json(['stats' => $stats]);
    }
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