<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\CountryConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CountryController extends Controller
{
    /**
     * Display a listing of countries.
     */
    public function index(Request $request)
    {
        try {
            $query = Country::with('configuration');

            // Filter by active status if provided
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Search by name or code
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            }

            $countries = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $countries
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve countries',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created country.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:2|unique:countries,code',
            'name' => 'required|string|max:255|unique:countries,name',
            'currency_code' => 'required|string|size:3',
            'currency_symbol' => 'required|string|max:10',
            'date_format' => 'required|string|max:50',
            'time_format' => 'required|string|max:50',
            'timezone' => 'required|string|max:100',
            'phone_code' => 'required|string|max:10',
            'is_active' => 'boolean',
            
            // Country Configuration fields (optional)
            'work_days_per_week' => 'nullable|integer|min:1|max:7',
            'hours_per_day' => 'nullable|numeric|min:1|max:24',
            'overtime_multiplier' => 'nullable|numeric|min:1',
            'annual_leave_days' => 'nullable|integer|min:0',
            'sick_leave_days' => 'nullable|integer|min:0',
            'public_holidays' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create country
            $country = Country::create([
                'code' => strtoupper($request->code),
                'name' => $request->name,
                'currency_code' => strtoupper($request->currency_code),
                'currency_symbol' => $request->currency_symbol,
                'date_format' => $request->date_format,
                'time_format' => $request->time_format,
                'timezone' => $request->timezone,
                'phone_code' => $request->phone_code,
                'is_active' => $request->boolean('is_active', true),
            ]);

            // Create country configuration if provided
            if ($request->has('work_days_per_week') || $request->has('hours_per_day')) {
                CountryConfiguration::create([
                    'country_id' => $country->id,
                    'work_days_per_week' => $request->work_days_per_week ?? 5,
                    'hours_per_day' => $request->hours_per_day ?? 8,
                    'overtime_multiplier' => $request->overtime_multiplier ?? 1.5,
                    'annual_leave_days' => $request->annual_leave_days ?? 20,
                    'sick_leave_days' => $request->sick_leave_days ?? 10,
                    'public_holidays' => $request->public_holidays ?? [],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Country created successfully',
                'data' => $country->load('configuration')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create country',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified country.
     */
    public function show(Country $country)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $country->load('configuration')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve country',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified country.
     */
    public function update(Request $request, Country $country)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:2|unique:countries,code,' . $country->id,
            'name' => 'required|string|max:255|unique:countries,name,' . $country->id,
            'currency_code' => 'required|string|size:3',
            'currency_symbol' => 'required|string|max:10',
            'date_format' => 'required|string|max:50',
            'time_format' => 'required|string|max:50',
            'timezone' => 'required|string|max:100',
            'phone_code' => 'required|string|max:10',
            'is_active' => 'boolean',
            
            // Country Configuration fields (optional)
            'work_days_per_week' => 'nullable|integer|min:1|max:7',
            'hours_per_day' => 'nullable|numeric|min:1|max:24',
            'overtime_multiplier' => 'nullable|numeric|min:1',
            'annual_leave_days' => 'nullable|integer|min:0',
            'sick_leave_days' => 'nullable|integer|min:0',
            'public_holidays' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Update country
            $country->update([
                'code' => strtoupper($request->code),
                'name' => $request->name,
                'currency_code' => strtoupper($request->currency_code),
                'currency_symbol' => $request->currency_symbol,
                'date_format' => $request->date_format,
                'time_format' => $request->time_format,
                'timezone' => $request->timezone,
                'phone_code' => $request->phone_code,
                'is_active' => $request->boolean('is_active', $country->is_active),
            ]);

            // Update or create country configuration
            if ($request->hasAny(['work_days_per_week', 'hours_per_day', 'overtime_multiplier', 'annual_leave_days', 'sick_leave_days'])) {
                $country->configuration()->updateOrCreate(
                    ['country_id' => $country->id],
                    [
                        'work_days_per_week' => $request->work_days_per_week ?? ($country->configuration->work_days_per_week ?? 5),
                        'hours_per_day' => $request->hours_per_day ?? ($country->configuration->hours_per_day ?? 8),
                        'overtime_multiplier' => $request->overtime_multiplier ?? ($country->configuration->overtime_multiplier ?? 1.5),
                        'annual_leave_days' => $request->annual_leave_days ?? ($country->configuration->annual_leave_days ?? 20),
                        'sick_leave_days' => $request->sick_leave_days ?? ($country->configuration->sick_leave_days ?? 10),
                        'public_holidays' => $request->public_holidays ?? ($country->configuration->public_holidays ?? []),
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Country updated successfully',
                'data' => $country->load('configuration')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update country',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified country.
     */
    public function destroy(Country $country)
    {
        try {
            // Check if country has employees
            if ($country->employees()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete country with existing employees'
                ], 422);
            }

            DB::beginTransaction();

            // Delete configuration first
            $country->configuration()->delete();
            
            // Delete country
            $country->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Country deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete country',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle country active status.
     */
    public function toggleStatus(Country $country)
    {
        try {
            $newStatus = !$country->is_active;
            
            $country->update([
                'is_active' => $newStatus
            ]);

            return response()->json([
                'success' => true,
                'message' => $newStatus ? 'Country activated successfully' : 'Country deactivated successfully',
                'data' => $country->load('configuration')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update country status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activate a country.
     */
    public function activate(Country $country)
    {
        try {
            $country->update([
                'is_active' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Country activated successfully',
                'data' => $country->load('configuration')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate country',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deactivate a country.
     */
    public function deactivate(Country $country)
    {
        try {
            $country->update([
                'is_active' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Country deactivated successfully',
                'data' => $country->load('configuration')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate country',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get country statistics.
     */
    public function statistics(Country $country)
    {
        try {
            $stats = [
                'total_employees' => $country->employees()->count(),
                'active_employees' => $country->employees()->where('is_active', true)->count(),
                'total_attendances' => $country->attendances()->count(),
                'total_leaves' => $country->leaves()->count(),
                'total_payrolls' => $country->payrolls()->count(),
                'total_payslips' => $country->payslips()->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'country' => $country->load('configuration'),
                    'statistics' => $stats
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve country statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}