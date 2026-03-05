<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportGeneratorService;
use App\Services\ReportExportService;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Payslip;
use App\Models\Business;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReportController extends Controller
{
    private $reportGeneratorService;
    private $reportExportService;

    public function __construct(
        ReportGeneratorService $reportGeneratorService,
        ReportExportService $reportExportService
    ) {
        $this->reportGeneratorService = $reportGeneratorService;
        $this->reportExportService = $reportExportService;
    }

    private function getBusinessScopedEmployees(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            Log::warning('REPORT_CONTROLLER: No authenticated user found');
            return Employee::where('id', 0);
        }

        $requestedBusinessId = $request->input('business_id');
        $requestedCountry = $request->input('country');
        
        $query = Employee::with(['user', 'manager', 'business', 'country']);

        Log::info('REPORT_CONTROLLER: Getting business scoped employees', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'requested_business_id' => $requestedBusinessId,
            'requested_country' => $requestedCountry,
        ]);

        // Admin logic
        if ($user->role === 'admin') {
            if ($requestedBusinessId) {
                $businessId = (int)$requestedBusinessId;
                
                if ($user->current_business_id && $user->current_business_id !== $businessId) {
                    if (!$user->businesses()->where('businesses.id', $businessId)->exists()) {
                        $query->where('business_id', 0);
                        return $query;
                    }
                }
                
                $query->where('business_id', $businessId);
            } elseif ($user->current_business_id) {
                $query->where('business_id', $user->current_business_id);
            } elseif ($user->businesses()->exists()) {
                $businessIds = $user->businesses()->pluck('businesses.id');
                $query->whereIn('business_id', $businessIds);
            }
        }
        // Manager logic
        elseif ($user->role === 'manager') {
            $managerEmployee = Employee::where('user_id', $user->id)->first();
            
            if ($managerEmployee && $managerEmployee->business_id) {
                $query->where('business_id', $managerEmployee->business_id)
                      ->where('manager_id', $user->id);
            } else {
                $query->where('id', 0);
            }
        }

        // Apply country filter if provided
        if ($requestedCountry) {
            // Handle both country_id and country_code
            if (is_numeric($requestedCountry)) {
                $query->where('country_id', $requestedCountry);
            } else {
                // It's a country code, look up the country
                $country = Country::where('code', $requestedCountry)->first();
                if ($country) {
                    $query->where('country_id', $country->id);
                } else {
                    // If country code not found, return no results
                    $query->where('country_id', 0);
                }
            }
        }

        return $query;
    }

    /**
     * Generate payroll report with filters and sub-filters - NO DEFAULTS
     */
    public function generatePayrollReport(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'department' => 'nullable|string',
                'status' => 'sometimes|in:all,paid,pending',
                'business_id' => 'sometimes|exists:businesses,id',
                'country' => 'sometimes|string',
                'deduction_type' => 'sometimes|in:all,tax,statutory,pension,health,levy,voluntary,other',
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            
            Log::info('REPORT_CONTROLLER: Generating payroll report', [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'filters' => $validated
            ]);
            
            // Get business-scoped employee IDs
            $employeeQuery = $this->getBusinessScopedEmployees($request);
            if (!empty($validated['department'])) {
                $employeeQuery->where('department', $validated['department']);
            }
            $employeeIds = $employeeQuery->pluck('id');
            
            Log::info('REPORT_CONTROLLER: Business scoped employee IDs found', [
                'count' => $employeeIds->count(),
                'ids' => $employeeIds->toArray()
            ]);
            
            if ($employeeIds->isEmpty()) {
                return $this->emptyPayrollResponse($validated, $startDate, $endDate);
            }
            
            // Prepare filters for the service
            $filters = [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'employee_ids' => $employeeIds->toArray(),
            ];
            
            // Add optional filters
            foreach (['status', 'business_id', 'country', 'department', 'deduction_type'] as $key) {
                if (!empty($validated[$key]) && $validated[$key] !== 'all') {
                    $filters[$key] = $validated[$key];
                }
            }
            
            // Generate the report using the service
            $reportData = $this->reportGeneratorService->generatePayrollReport($filters);
            
            // Format the response - NO DEFAULTS
            $response = [
                'success' => true,
                'message' => 'Payroll report generated successfully',
                'data' => [
                    'department' => $validated['department'] ?? 'All Departments',
                    
                    // Keep as raw numbers
                    'total_gross_salary' => $reportData['summary']['total_gross_salary'] ?? 0,
                    'total_net_salary' => $reportData['summary']['total_net_salary'] ?? 0,
                    'total_earnings' => $reportData['summary']['total_earnings'] ?? 0,
                    'total_all_deductions' => $reportData['summary']['total_all_deductions'] ?? 0,
                    'total_paye_tax' => $reportData['summary']['total_paye_tax'] ?? 0,
                    'processed_employees' => $reportData['summary']['processed_employees'] ?? 0,
                    'average_gross_salary' => $reportData['summary']['average_gross_salary'] ?? 0,
                    'average_net_salary' => $reportData['summary']['average_net_salary'] ?? 0,
                    
                    // Earning headers and totals
                    'earning_headers' => $reportData['summary']['earning_headers'] ?? [],
                    'earning_totals' => $reportData['summary']['earning_totals'] ?? [],
                    'earning_breakdown' => $reportData['summary']['earning_breakdown'] ?? [],
                    
                    // Deduction headers and totals
                    'deduction_headers' => $reportData['summary']['deduction_headers'] ?? [],
                    'deduction_totals' => $reportData['summary']['deduction_totals'] ?? [],
                    'deduction_breakdown' => $reportData['summary']['deduction_breakdown'] ?? [],
                    
                    // Legacy support
                    'dynamic_headers' => array_merge(
                        $reportData['summary']['earning_headers'] ?? [],
                        $reportData['summary']['deduction_headers'] ?? []
                    ),
                    
                    'payslip_details' => $reportData['data'] ?? [],
                    'department_breakdown' => $reportData['summary']['department_breakdown'] ?? [],
                    
                    'period' => $startDate->format('M d, Y') . ' to ' . $endDate->format('M d, Y'),
                    'period_start' => $startDate->format('Y-m-d'),
                    'period_end' => $endDate->format('Y-m-d'),
                    'generated_at' => $reportData['generated_at']->toDateTimeString(),
                    'filters' => $reportData['summary']['filters'] ?? [],
                    
                    // Currency - only included if they exist in the data
                    'currency' => isset($reportData['summary']['currency']) ? $reportData['summary']['currency'] : null,
                    'currency_symbol' => isset($reportData['summary']['currency_symbol']) ? $reportData['summary']['currency_symbol'] : null,
                ],
                'type' => 'payroll'
            ];
            
            Log::info('REPORT_CONTROLLER: Payroll report generated successfully', [
                'processed_employees' => $reportData['summary']['processed_employees'] ?? 0,
                'earning_types' => count($reportData['summary']['earning_headers'] ?? []),
                'deduction_types' => count($reportData['summary']['deduction_headers'] ?? []),
                'total_gross' => $reportData['summary']['total_gross_salary'] ?? 0,
                'currency' => $reportData['summary']['currency'] ?? 'not set',
                'currency_symbol' => $reportData['summary']['currency_symbol'] ?? 'not set'
            ]);
            
            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('REPORT_CONTROLLER: Error generating payroll report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate payroll report: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate earnings-only report - NO DEFAULTS
     */
    public function generateEarningsReport(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'department' => 'nullable|string',
                'status' => 'sometimes|in:all,paid,pending',
                'business_id' => 'sometimes|exists:businesses,id',
                'country' => 'sometimes|string',
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            
            Log::info('REPORT_CONTROLLER: Generating earnings report', [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'filters' => $validated
            ]);
            
            // Get business-scoped employee IDs
            $employeeQuery = $this->getBusinessScopedEmployees($request);
            if (!empty($validated['department'])) {
                $employeeQuery->where('department', $validated['department']);
            }
            $employeeIds = $employeeQuery->pluck('id');
            
            Log::info('REPORT_CONTROLLER: Business scoped employee IDs found', [
                'count' => $employeeIds->count(),
                'ids' => $employeeIds->toArray()
            ]);
            
            if ($employeeIds->isEmpty()) {
                return $this->emptyEarningsResponse($validated, $startDate, $endDate);
            }
            
            // Prepare filters for the service
            $filters = [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'employee_ids' => $employeeIds->toArray(),
            ];
            
            // Add optional filters
            foreach (['status', 'business_id', 'country', 'department'] as $key) {
                if (!empty($validated[$key]) && $validated[$key] !== 'all') {
                    $filters[$key] = $validated[$key];
                }
            }
            
            // Generate the earnings report
            $reportData = $this->reportGeneratorService->generateEarningsReport($filters);
            
            // Format the response - NO DEFAULTS
            $response = [
                'success' => true,
                'message' => 'Earnings report generated successfully',
                'data' => [
                    'department' => $validated['department'] ?? 'All Departments',
                    
                    // Raw numbers
                    'total_earnings' => $reportData['summary']['total_earnings'] ?? 0,
                    'total_gross_salary' => $reportData['summary']['total_gross_salary'] ?? 0,
                    'processed_employees' => $reportData['summary']['processed_employees'] ?? 0,
                    'average_earnings' => $reportData['summary']['average_earnings'] ?? 0,
                    
                    // Dynamic earning information
                    'earning_totals' => $reportData['summary']['earning_totals'] ?? [],
                    'earning_breakdown' => $reportData['summary']['earning_breakdown'] ?? [],
                    'earnings_by_type' => $reportData['summary']['earnings_by_type'] ?? [],
                    'earning_headers' => $reportData['summary']['earning_headers'] ?? [],
                    
                    'employee_earnings' => $reportData['data'] ?? [],
                    'department_breakdown' => $reportData['summary']['department_breakdown'] ?? [],
                    
                    'period' => $startDate->format('M d, Y') . ' to ' . $endDate->format('M d, Y'),
                    'generated_at' => $reportData['generated_at']->toDateTimeString(),
                    'filters' => $reportData['summary']['filters'] ?? [],
                    
                    // Currency - only if exists
                    'currency' => isset($reportData['summary']['currency']) ? $reportData['summary']['currency'] : null,
                    'currency_symbol' => isset($reportData['summary']['currency_symbol']) ? $reportData['summary']['currency_symbol'] : null,
                ],
                'type' => 'earnings'
            ];
            
            Log::info('REPORT_CONTROLLER: Earnings report generated successfully', [
                'processed_employees' => $reportData['summary']['processed_employees'] ?? 0,
                'unique_earnings' => count($reportData['summary']['earning_headers'] ?? []),
                'total_earnings' => $reportData['summary']['total_earnings'] ?? 0,
            ]);
            
            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('REPORT_CONTROLLER: Error generating earnings report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate earnings report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate deductions-only report - NO DEFAULTS
     */
    public function generateDeductionsReport(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'department' => 'nullable|string',
                'status' => 'sometimes|in:all,paid,pending',
                'business_id' => 'sometimes|exists:businesses,id',
                'country' => 'sometimes|string',
                'deduction_type' => 'sometimes|in:all,tax,statutory,pension,health,levy,voluntary,other',
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            
            Log::info('REPORT_CONTROLLER: Generating deductions report', [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'filters' => $validated
            ]);
            
            // Get business-scoped employee IDs
            $employeeQuery = $this->getBusinessScopedEmployees($request);
            if (!empty($validated['department'])) {
                $employeeQuery->where('department', $validated['department']);
            }
            $employeeIds = $employeeQuery->pluck('id');
            
            Log::info('REPORT_CONTROLLER: Business scoped employee IDs found', [
                'count' => $employeeIds->count(),
                'ids' => $employeeIds->toArray()
            ]);
            
            if ($employeeIds->isEmpty()) {
                return $this->emptyDeductionsResponse($validated, $startDate, $endDate);
            }
            
            // Prepare filters for the service
            $filters = [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'employee_ids' => $employeeIds->toArray(),
            ];
            
            // Add optional filters
            foreach (['status', 'business_id', 'country', 'department', 'deduction_type'] as $key) {
                if (!empty($validated[$key]) && $validated[$key] !== 'all') {
                    $filters[$key] = $validated[$key];
                }
            }
            
            // Generate the deductions report
            $reportData = $this->reportGeneratorService->generateDeductionsReport($filters);
            
            // Format the response - NO DEFAULTS
            $response = [
                'success' => true,
                'message' => 'Deductions report generated successfully',
                'data' => [
                    'department' => $validated['department'] ?? 'All Departments',
                    
                    // Raw numbers
                    'total_deductions' => $reportData['summary']['total_deductions'] ?? 0,
                    'total_paye_tax' => $reportData['summary']['total_paye_tax'] ?? 0,
                    'processed_employees' => $reportData['summary']['processed_employees'] ?? 0,
                    'average_deductions' => $reportData['summary']['average_deductions'] ?? 0,
                    
                    // Dynamic deduction information
                    'deduction_totals' => $reportData['summary']['deduction_totals'] ?? [],
                    'deduction_breakdown' => $reportData['summary']['deduction_breakdown'] ?? [],
                    'deductions_by_type' => $reportData['summary']['deductions_by_type'] ?? [],
                    'deduction_headers' => $reportData['summary']['deduction_headers'] ?? [],
                    
                    'employee_deductions' => $reportData['data'] ?? [],
                    'department_breakdown' => $reportData['summary']['department_breakdown'] ?? [],
                    
                    'period' => $startDate->format('M d, Y') . ' to ' . $endDate->format('M d, Y'),
                    'generated_at' => $reportData['generated_at']->toDateTimeString(),
                    'filters' => $reportData['summary']['filters'] ?? [],
                    
                    // Currency - only if exists
                    'currency' => isset($reportData['summary']['currency']) ? $reportData['summary']['currency'] : null,
                    'currency_symbol' => isset($reportData['summary']['currency_symbol']) ? $reportData['summary']['currency_symbol'] : null,
                ],
                'type' => 'deductions'
            ];
            
            Log::info('REPORT_CONTROLLER: Deductions report generated successfully', [
                'processed_employees' => $reportData['summary']['processed_employees'] ?? 0,
                'unique_deductions' => count($reportData['summary']['deduction_headers'] ?? []),
                'total_deductions' => $reportData['summary']['total_deductions'] ?? 0,
            ]);
            
            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('REPORT_CONTROLLER: Error generating deductions report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate deductions report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download report - NO DEFAULTS
     */
    public function downloadReport(Request $request, $type)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $validated = $request->validate([
                'format' => 'required|in:pdf,csv',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'department' => 'sometimes|string',
                'status' => 'sometimes|in:all,paid,pending,approved,rejected',
                'business_id' => 'sometimes|exists:businesses,id',
                'country' => 'sometimes|string',
            ]);

            Log::info("REPORT_CONTROLLER: Starting download for {$type}", [
                'filters' => $validated
            ]);

            $filters = $validated;
            $format = $validated['format'];
            
            // Generate the appropriate report based on type
            switch ($type) {
                case 'payroll':
                    $reportRequest = new Request($filters);
                    $reportRequest->setUserResolver(fn() => $user);
                    
                    $reportResponse = $this->generatePayrollReport($reportRequest);
                    $reportData = $reportResponse->getData(true);
                    
                    $filename = "payroll_report_" . now()->format('Y-m-d_His') . "." . $format;
                    $view = 'reports.payroll';
                    
                    Log::info('REPORT_CONTROLLER: Payroll report data for export', [
                        'has_data' => isset($reportData['data']),
                        'data_count' => count($reportData['data']['payslip_details'] ?? []),
                        'has_currency' => isset($reportData['data']['currency']),
                        'currency' => $reportData['data']['currency'] ?? 'not set'
                    ]);
                    
                    if ($format === 'csv') {
                        return $this->reportExportService->exportToCsv(
                            $reportData['data'],
                            $filename,
                            'payroll'
                        );
                    }
                    break;
                    
                case 'earnings':
                    $reportRequest = new Request($filters);
                    $reportRequest->setUserResolver(fn() => $user);
                    
                    $reportResponse = $this->generateEarningsReport($reportRequest);
                    $reportData = $reportResponse->getData(true);
                    $filename = "earnings_report_" . now()->format('Y-m-d_His') . "." . $format;
                    $view = 'reports.earnings';
                    
                    if ($format === 'csv') {
                        return $this->reportExportService->exportToCsv(
                            $reportData['data'],
                            $filename,
                            'earnings'
                        );
                    }
                    break;
                    
                case 'deductions':
                    $reportRequest = new Request($filters);
                    $reportRequest->setUserResolver(fn() => $user);
                    
                    $reportResponse = $this->generateDeductionsReport($reportRequest);
                    $reportData = $reportResponse->getData(true);
                    $filename = "deductions_report_" . now()->format('Y-m-d_His') . "." . $format;
                    $view = 'reports.deductions';
                    
                    if ($format === 'csv') {
                        return $this->reportExportService->exportToCsv(
                            $reportData['data'],
                            $filename,
                            'deductions'
                        );
                    }
                    break;
                    
                case 'attendance':
                    $reportRequest = new Request($filters);
                    $reportRequest->setUserResolver(fn() => $user);
                    
                    $reportResponse = $this->generateAttendanceReport($reportRequest);
                    $reportData = $reportResponse->getData(true);
                    $filename = "attendance_report_" . now()->format('Y-m-d_His') . "." . $format;
                    $view = 'reports.attendance';
                    break;
                    
                case 'leave':
                    $reportRequest = new Request($filters);
                    $reportRequest->setUserResolver(fn() => $user);
                    
                    $reportResponse = $this->generateLeaveReport($reportRequest);
                    $reportData = $reportResponse->getData(true);
                    $filename = "leave_report_" . now()->format('Y-m-d_His') . "." . $format;
                    $view = 'reports.leave';
                    break;
                    
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid report type'
                    ], 422);
            }

            Log::info("REPORT_CONTROLLER: Generating {$type} report for export", [
                'format' => $format,
                'filename' => $filename,
                'data_count' => count($reportData['data'] ?? [])
            ]);

            if ($format === 'pdf') {
                return $this->reportExportService->exportToPdf($view, $reportData, $filename);
            } else {
                return $this->reportExportService->exportToCsv(
                    $reportData['data'] ?? [],
                    $filename,
                    $type
                );
            }

        } catch (\Exception $e) {
            Log::error("REPORT_CONTROLLER: Export {$type} report error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to export report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Return empty deductions response - NO DEFAULTS
     */
    private function emptyDeductionsResponse(array $validated, Carbon $startDate, Carbon $endDate): JsonResponse
    {
        Log::info('REPORT_CONTROLLER: Empty deductions response - no employees found', [
            'filters' => $validated
        ]);
        
        $filterInfo = $this->buildFilterInfo($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Deductions report generated successfully (no payslips found for selected criteria)',
            'data' => [
                'department' => $validated['department'] ?? 'All Departments',
                'total_deductions' => 0,
                'total_paye_tax' => 0,
                'processed_employees' => 0,
                'average_deductions' => 0,
                'deduction_totals' => [],
                'deduction_breakdown' => [],
                'deductions_by_type' => [],
                'deduction_headers' => [],
                'employee_deductions' => [],
                'department_breakdown' => [],
                'period' => $startDate->format('M d, Y') . ' to ' . $endDate->format('M d, Y'),
                'generated_at' => now()->toDateTimeString(),
                'filters' => $filterInfo,
                
                // Currency only if it exists in filters
                'currency' => isset($filterInfo['currency']) ? $filterInfo['currency'] : null,
                'currency_symbol' => isset($filterInfo['currency_symbol']) ? $filterInfo['currency_symbol'] : null,
            ],
            'type' => 'deductions'
        ]);
    }
    
    /**
     * Return empty earnings response - NO DEFAULTS
     */
    private function emptyEarningsResponse(array $validated, Carbon $startDate, Carbon $endDate): JsonResponse
    {
        Log::info('REPORT_CONTROLLER: Empty earnings response - no employees found', [
            'filters' => $validated
        ]);
        
        $filterInfo = $this->buildFilterInfo($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Earnings report generated successfully (no payslips found for selected criteria)',
            'data' => [
                'department' => $validated['department'] ?? 'All Departments',
                'total_earnings' => 0,
                'total_gross_salary' => 0,
                'processed_employees' => 0,
                'average_earnings' => 0,
                'earning_totals' => [],
                'earning_breakdown' => [],
                'earnings_by_type' => [],
                'earning_headers' => [],
                'employee_earnings' => [],
                'department_breakdown' => [],
                'period' => $startDate->format('M d, Y') . ' to ' . $endDate->format('M d, Y'),
                'generated_at' => now()->toDateTimeString(),
                'filters' => $filterInfo,
                
                // Currency only if it exists in filters
                'currency' => isset($filterInfo['currency']) ? $filterInfo['currency'] : null,
                'currency_symbol' => isset($filterInfo['currency_symbol']) ? $filterInfo['currency_symbol'] : null,
            ],
            'type' => 'earnings'
        ]);
    }

    /**
     * Return empty payroll response - NO DEFAULTS
     */
    private function emptyPayrollResponse(array $validated, Carbon $startDate, Carbon $endDate): JsonResponse
    {
        Log::info('REPORT_CONTROLLER: Empty payroll response - no employees found', [
            'filters' => $validated
        ]);
        
        $filterInfo = $this->buildFilterInfo($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Payroll report generated successfully (no payslips found for selected criteria)',
            'data' => [
                'department' => $validated['department'] ?? 'All Departments',
                'total_gross_salary' => 0,
                'total_net_salary' => 0,
                'total_earnings' => 0,
                'total_all_deductions' => 0,
                'total_paye_tax' => 0,
                'processed_employees' => 0,
                'average_gross_salary' => 0,
                'average_net_salary' => 0,
                
                // Empty dynamic structures
                'earning_totals' => [],
                'deduction_totals' => [],
                'earning_breakdown' => [],
                'deduction_breakdown' => [],
                'earnings_by_type' => [],
                'deductions_by_type' => [],
                'earning_headers' => [],
                'deduction_headers' => [],
                
                'payslip_details' => [],
                'department_breakdown' => [],
                
                'period' => $startDate->format('M d, Y') . ' to ' . $endDate->format('M d, Y'),
                'period_start' => $startDate->format('Y-m-d'),
                'period_end' => $endDate->format('Y-m-d'),
                'generated_at' => now()->toDateTimeString(),
                'filters' => $filterInfo,
                
                // Currency only if it exists in filters
                'currency' => isset($filterInfo['currency']) ? $filterInfo['currency'] : null,
                'currency_symbol' => isset($filterInfo['currency_symbol']) ? $filterInfo['currency_symbol'] : null,
            ],
            'type' => 'payroll'
        ]);
    }

    /**
     * Helper method to build filter info for reports - NO DEFAULTS
     */
    private function buildFilterInfo(array $validated): array
    {
        $filterInfo = [];
        
        if (!empty($validated['business_id'])) {
            $business = Business::find($validated['business_id']);
            $filterInfo['business'] = $business ? $business->name : null;
            $filterInfo['business_id'] = $validated['business_id'];
        }
        
        if (!empty($validated['country'])) {
            if (is_numeric($validated['country'])) {
                $country = Country::find($validated['country']);
            } else {
                $country = Country::where('code', $validated['country'])->first();
            }
            
            if ($country) {
                $filterInfo['country'] = $country->name;
                $filterInfo['country_code'] = $country->code;
                $filterInfo['currency'] = $country->currency;
                $filterInfo['currency_symbol'] = $country->currency_symbol;
            }
            // No else clause - don't set defaults
        }
        
        if (!empty($validated['department'])) {
            $filterInfo['department'] = $validated['department'];
        }
        
        if (!empty($validated['start_date'])) {
            $filterInfo['start_date'] = $validated['start_date'];
        }
        
        if (!empty($validated['end_date'])) {
            $filterInfo['end_date'] = $validated['end_date'];
        }
        
        if (!empty($validated['status']) && $validated['status'] !== 'all') {
            $filterInfo['status'] = $validated['status'];
        }
        
        if (!empty($validated['deduction_type']) && $validated['deduction_type'] !== 'all') {
            $filterInfo['deduction_type'] = $validated['deduction_type'];
        }
        
        return $filterInfo;
    }

    /**
     * Generate attendance report with business and country filters
     */
    public function generateAttendanceReport(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'department' => 'sometimes|string',
                'business_id' => 'sometimes|exists:businesses,id',
                'country' => 'sometimes|exists:countries,id',
                'report_type' => 'sometimes|in:summary,detailed,daily'
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            
            Log::info('REPORT_CONTROLLER: Generating attendance report', [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'department' => $validated['department'] ?? 'All',
                'business_id' => $validated['business_id'] ?? null,
                'country' => $validated['country'] ?? null,
            ]);
            
            // Generate the report using the service
            $reportData = $this->reportGeneratorService->generateAttendanceReport($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Attendance report generated successfully',
                'data' => $reportData,
                'type' => 'attendance'
            ]);

        } catch (\Exception $e) {
            Log::error('REPORT_CONTROLLER: Error generating attendance report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate attendance report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate leave report with business and country filters
     */
    public function generateLeaveReport(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'leave_type' => 'sometimes|string',
                'status' => 'sometimes|in:pending,approved,rejected,all',
                'business_id' => 'sometimes|exists:businesses,id',
                'country' => 'sometimes|exists:countries,id'
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            
            Log::info('REPORT_CONTROLLER: Generating leave report', [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'leave_type' => $validated['leave_type'] ?? 'All',
                'status' => $validated['status'] ?? 'All',
                'business_id' => $validated['business_id'] ?? null,
                'country' => $validated['country'] ?? null,
            ]);
            
            // Generate the report using the service
            $reportData = $this->reportGeneratorService->generateLeaveReport($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Leave report generated successfully',
                'data' => $reportData,
                'type' => 'leave'
            ]);

        } catch (\Exception $e) {
            Log::error('REPORT_CONTROLLER: Error generating leave report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate leave report: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAdminStats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'total_employees' => 0,
                    'present_today' => 0,
                    'pending_leaves' => 0,
                    'avg_attendance' => 0,
                    'month' => Carbon::now()->format('F Y')
                ]);
            }

            // Use business-scoped employees
            $employeeQuery = $this->getBusinessScopedEmployees($request);
            $totalEmployees = $employeeQuery->count();
            
            // Get business-scoped employee IDs for attendance
            $employeeIds = $employeeQuery->pluck('id');
            
            Log::info('REPORT_CONTROLLER: Getting admin stats', [
                'total_employees' => $totalEmployees,
                'employee_ids_count' => $employeeIds->count(),
                'user_id' => $user->id,
                'user_role' => $user->role,
                'requested_business_id' => $request->input('business_id'),
                'requested_country' => $request->input('country'),
            ]);
            
            $today = Carbon::today()->toDateString();
            $presentToday = Attendance::whereDate('date', $today)
                ->where('status', 'present')
                ->whereIn('employee_id', $employeeIds)
                ->count();
            
            $pendingLeaves = Leave::where('status', 'pending')
                ->whereIn('employee_id', $employeeIds)
                ->count();
            
            $currentMonthStart = Carbon::now()->startOfMonth();
            $currentMonthEnd = Carbon::now()->endOfMonth();
            
            $totalAttendanceDays = Attendance::whereBetween('date', [$currentMonthStart, $currentMonthEnd])
                ->where('status', 'present')
                ->whereIn('employee_id', $employeeIds)
                ->count();
                
            $workingDays = $currentMonthStart->diffInDaysFiltered(function (Carbon $date) {
                return !$date->isWeekend();
            }, $currentMonthEnd);
            
            $avgAttendance = ($workingDays > 0 && $totalEmployees > 0) 
                ? round(($totalAttendanceDays / ($totalEmployees * $workingDays)) * 100, 2) 
                : 0;

            return response()->json([
                'total_employees' => $totalEmployees,
                'present_today' => $presentToday,
                'pending_leaves' => $pendingLeaves,
                'avg_attendance' => $avgAttendance,
                'month' => Carbon::now()->format('F Y')
            ]);

        } catch (\Exception $e) {
            Log::error('REPORT_CONTROLLER: Error fetching admin stats', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'total_employees' => 0,
                'present_today' => 0,
                'pending_leaves' => 0,
                'avg_attendance' => 0,
                'month' => Carbon::now()->format('F Y')
            ]);
        }
    }

    public function teamReport(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'department' => 'sometimes|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);
        
        $reportData = $this->generateTeamReport($request, $filters);
        return response()->json($reportData);
    }

    /**
     * Generate payment report (employee payments only)
     */
    public function generatePaymentReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department' => 'nullable|string',
            'status' => 'sometimes|in:all,paid,pending',
            'business_id' => 'sometimes|exists:businesses,id',
            'country' => 'sometimes|string',
        ]);

        try {
            $filters = $this->prepareFilters($request, $validated);
            $reportData = $this->reportGeneratorService->generatePaymentReport($filters);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment report generated successfully',
                'data' => $reportData,
                'type' => 'payment'
            ]);

        } catch (\Exception $e) {
            Log::error('REPORT_CONTROLLER: Error generating payment report', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate payment report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export payroll report to PDF or CSV
     */
    public function exportPayrollReport(Request $request): mixed
    {
        $validated = $request->validate([
            'format' => 'required|in:pdf,csv',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department' => 'nullable|string',
            'status' => 'sometimes|in:all,paid,pending',
            'business_id' => 'sometimes|exists:businesses,id',
            'country' => 'sometimes|string',
        ]);

        try {
            $filters = $this->prepareFilters($request, $validated);
            $reportData = $this->reportGeneratorService->generatePayrollReport($filters);
            
            $filename = "payroll_report_" . now()->format('Y-m-d_His') . "." . $validated['format'];
            
            if ($validated['format'] === 'pdf') {
                return $this->reportExportService->exportToPdf(
                    'reports.payroll',
                    $reportData,
                    $filename
                );
            } else {
                return $this->reportExportService->exportToCsv(
                    $reportData['data'] ?? [],
                    $reportData['summary']['dynamic_headers'] ?? [],
                    $filename
                );
            }

        } catch (\Exception $e) {
            Log::error('REPORT_CONTROLLER: Export error', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to export report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prepare filters from request
     */
    private function prepareFilters(Request $request, array $validated): array
    {
        $employeeQuery = $this->getBusinessScopedEmployees($request);
        if (!empty($validated['department'])) {
            $employeeQuery->where('department', $validated['department']);
        }
        $employeeIds = $employeeQuery->pluck('id');
        
        $filters = [
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'employee_ids' => $employeeIds->toArray(),
        ];
        
        foreach (['status', 'business_id', 'country', 'department', 'deduction_type'] as $key) {
            if (!empty($validated[$key]) && $validated[$key] !== 'all') {
                $filters[$key] = $validated[$key];
            }
        }
        
        return $filters;
    }

    public function payrollReport(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'status' => 'sometimes|in:draft,processing,completed,failed',
        ]);
        
        $filters['request'] = $request;
        $reportData = $this->reportGeneratorService->generatePayrollReport($filters);
        return response()->json($reportData);
    }

    public function attendanceReport(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'employee_id' => 'sometimes|exists:employees,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'status' => 'sometimes|in:present,absent,late,half_day',
        ]);
        
        $filters['request'] = $request;
        $reportData = $this->reportGeneratorService->generateAttendanceReport($filters);
        return response()->json($reportData);
    }

    public function leaveReport(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'employee_id' => 'sometimes|exists:employees,id',
            'type' => 'sometimes|in:vacation,sick,personal,maternity,paternity',
            'status' => 'sometimes|in:pending,approved,rejected',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);
        
        $filters['request'] = $request;
        $reportData = $this->reportGeneratorService->generateLeaveReport($filters);
        return response()->json($reportData);
    }

    public function productivityReport(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'employee_id' => 'sometimes|exists:employees,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'department' => 'sometimes|string',
        ]);
        
        $reportData = $this->generateProductivityReport($request, $filters);
        return response()->json($reportData);
    }

    public function getReportParams($type): JsonResponse
    {
        $defaults = [
            'start_date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->endOfMonth()->format('Y-m-d'),
        ];

        switch ($type) {
            case 'attendance':
                $defaults['department'] = '';
                $defaults['report_type'] = 'summary';
                break;
            case 'leave':
                $defaults['leave_type'] = '';
                $defaults['status'] = '';
                break;
            case 'payroll':
                $defaults['department'] = '';
                $defaults['status'] = 'all';
                break;
        }

        return response()->json($defaults);
    }

    /**
     * Get generated reports list
     */
    public function getGeneratedReports(): JsonResponse
    {
        return response()->json([]);
    }

    /**
     * Generate team report data with business scoping
     */
    private function generateTeamReport(Request $request, array $filters): array
    {
        $query = $this->getBusinessScopedEmployees($request)
            ->with(['user', 'attendances', 'leaves']);
        
        if (isset($filters['department'])) {
            $query->where('department', $filters['department']);
        }
        
        $employees = $query->get();
        
        $totalEmployees = $employees->count();
        
        $today = now()->format('Y-m-d');
        $presentToday = Attendance::whereDate('date', $today)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->where('status', 'present')
            ->count();
            
        $pendingLeaves = Leave::whereIn('employee_id', $employees->pluck('id'))
            ->where('status', 'pending')
            ->count();
            
        $totalProductivity = 0;
        $employeesWithProductivity = 0;
        foreach ($employees as $employee) {
            $productivity = $this->calculateEmployeeProductivity($employee, $filters);
            if ($productivity !== null) {
                $totalProductivity += $productivity;
                $employeesWithProductivity++;
            }
        }
        $avgProductivity = $employeesWithProductivity > 0 ? round($totalProductivity / $employeesWithProductivity) : 0;
        
        return [
            'data' => [
                'department' => $filters['department'] ?? 'All Departments',
                'period_start' => $filters['start_date'] ?? now()->startOfMonth()->format('Y-m-d'),
                'period_end' => $filters['end_date'] ?? now()->format('Y-m-d'),
                'total_employees' => $totalEmployees,
                'active_employees' => $totalEmployees,
                'present_today' => $presentToday,
                'on_leave' => $pendingLeaves,
                'avg_productivity' => $avgProductivity,
                'team_members' => $employees->map(function ($employee) use ($filters) {
                    return [
                        'id' => $employee->id,
                        'name' => $employee->full_name ?? ($employee->user->first_name . ' ' . $employee->user->last_name),
                        'email' => $employee->user->email,
                        'department' => $employee->department,
                        'position' => $employee->position ?? 'N/A',
                        'status' => 'active',
                        'productivity' => $this->calculateEmployeeProductivity($employee, $filters),
                        'last_attendance' => $employee->attendances->sortByDesc('date')->first()->date ?? 'N/A',
                    ];
                })
            ],
            'summary' => [
                'team_size' => $totalEmployees,
                'present_today' => $presentToday,
                'pending_leaves' => $pendingLeaves,
                'avg_productivity' => $avgProductivity,
                'attendance_rate' => $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100) : 0,
            ]
        ];
    }

    /**
     * Generate productivity report data with business scoping
     */
    private function generateProductivityReport(Request $request, array $filters): array
    {
        $query = $this->getBusinessScopedEmployees($request)
            ->with(['user', 'attendances']);
        
        if (isset($filters['employee_id'])) {
            $query->where('id', $filters['employee_id']);
        }
        
        if (isset($filters['department'])) {
            $query->where('department', $filters['department']);
        }
        
        $employees = $query->get();
        $reportData = $employees->map(function ($employee) use ($filters) {
            $productivity = $this->calculateEmployeeProductivity($employee, $filters);
            $tasksCompleted = $this->calculateTaskCompletionRate($employee, $filters);
            $attendanceRate = $this->calculateAttendanceRate($employee, $filters);
            return [
                'id' => $employee->id,
                'name' => $employee->full_name ?? ($employee->user->first_name . ' ' . $employee->user->last_name),
                'email' => $employee->user->email,
                'department' => $employee->department,
                'position' => $employee->position ?? 'N/A',
                'productivity_score' => $productivity,
                'tasks_completed' => $tasksCompleted,
                'attendance_rate' => $attendanceRate,
                'status' => 'active',
            ];
        });
        $totalProductivity = $reportData->sum('productivity_score');
        $avgProductivity = $reportData->count() > 0 ? round($totalProductivity / $reportData->count()) : 0;
        $totalTasksCompleted = $reportData->sum('tasks_completed');
        $avgAttendanceRate = $reportData->count() > 0 ? round($reportData->avg('attendance_rate')) : 0;
        return [
            'data' => $reportData,
            'summary' => [
                'total_employees' => $reportData->count(),
                'avg_productivity' => $avgProductivity,
                'total_tasks_completed' => $totalTasksCompleted,
                'avg_attendance_rate' => $avgAttendanceRate,
                'generated_at' => now()->toISOString(),
            ]
        ];
    }

    /**
     * Calculate employee productivity score (0-100)
     */
    private function calculateEmployeeProductivity(Employee $employee, array $filters): int
    {
        $score = 0;
        $factors = 0;
        
        $attendanceRate = $this->calculateAttendanceRate($employee, $filters);
        $score += $attendanceRate * 0.4;
        $factors++;
        
        $taskCompletionRate = $this->calculateTaskCompletionRate($employee, $filters);
        $score += $taskCompletionRate * 0.4;
        $factors++;
        
        $punctualityRate = $this->calculatePunctualityRate($employee, $filters);
        $score += $punctualityRate * 0.2;
        $factors++;
        
        return min(100, max(0, round($score)));
    }

    /**
     * Calculate attendance rate for an employee
     */
    private function calculateAttendanceRate(Employee $employee, array $filters): int
    {
        $attendanceQuery = $employee->attendances();
        
        if (isset($filters['start_date'])) {
            $attendanceQuery->where('date', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $attendanceQuery->where('date', '<=', $filters['end_date']);
        }
        $totalDays = $attendanceQuery->count();
        $presentDays = $attendanceQuery->where('status', 'present')->count();
        return $totalDays > 0 ? round(($presentDays / $totalDays) * 100) : 0;
    }

    /**
     * Calculate task completion rate for an employee
     */
    private function calculateTaskCompletionRate(Employee $employee, array $filters): int
    {
        return 80;
    }

    /**
     * Calculate punctuality rate for an employee
     */
    private function calculatePunctualityRate(Employee $employee, array $filters): int
    {
        $attendanceQuery = $employee->attendances()->where('status', 'present');
        
        if (isset($filters['start_date'])) {
            $attendanceQuery->where('date', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $attendanceQuery->where('date', '<=', $filters['end_date']);
        }
        $totalAttendance = $attendanceQuery->count();
        $onTimeAttendance = $attendanceQuery->whereTime('clock_in', '<=', '09:15:00')->count();
        return $totalAttendance > 0 ? round(($onTimeAttendance / $totalAttendance) * 100) : 100;
    }

    public function generateOrganizationReport(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);
        
        try {
            $startDate = $filters['start_date'] ?? now()->startOfMonth()->format('Y-m-d');
            $endDate = $filters['end_date'] ?? now()->format('Y-m-d');
            
            $attendanceReport = $this->generateAttendanceReport(new Request($filters))->getData(true);
            $leaveReport = $this->generateLeaveReport(new Request($filters))->getData(true);
            $payrollReport = $this->generatePayrollReport(new Request($filters))->getData(true);
            
            // Get business-scoped totals
            $employeeQuery = $this->getBusinessScopedEmployees($request);
            $totalEmployees = $employeeQuery->count();
            $employeeIds = $employeeQuery->pluck('id');
            
            $totalDepartments = $employeeQuery->distinct('department')->count('department');
            $presentToday = Attendance::whereDate('date', now()->format('Y-m-d'))
                ->where('status', 'present')
                ->whereIn('employee_id', $employeeIds)
                ->count();
            $pendingLeaves = Leave::where('status', 'pending')
                ->whereIn('employee_id', $employeeIds)
                ->count();

            $organizationReport = [
                'period_start' => $startDate,
                'period_end' => $endDate,
                'report_type' => 'organization_overview',
                'organization_stats' => [
                    'total_employees' => $totalEmployees,
                    'total_departments' => $totalDepartments,
                    'present_today' => $presentToday,
                    'pending_leaves' => $pendingLeaves,
                    'attendance_rate' => $attendanceReport['data']['attendance_rate'] ?? 0,
                    'leave_approval_rate' => $leaveReport['data']['approval_rate'] ?? 0,
                ],
                'attendance_summary' => $attendanceReport['data']['attendance_summary'] ?? [],
                'leave_summary' => [
                    'total_requests' => $leaveReport['data']['total_leave_requests'] ?? 0,
                    'approved' => $leaveReport['data']['status_breakdown']['approved'] ?? 0,
                    'pending' => $leaveReport['data']['status_breakdown']['pending'] ?? 0,
                ],
                'payroll_summary' => [
                    'total_processed' => $payrollReport['data']['processed_employees'] ?? 0,
                    'total_payroll' => $payrollReport['data']['total_net_salary'] ?? 0,
                    'average_salary' => $payrollReport['data']['average_net_salary'] ?? 0,
                ],
                'department_performance' => $this->getDepartmentPerformance($request, $filters),
                'generated_at' => now()->toDateTimeString(),
            ];
            return response()->json([
                'success' => true,
                'message' => 'Organization report generated successfully',
                'report' => $organizationReport
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating organization report: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate organization report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get department performance metrics with business scoping
     */
    private function getDepartmentPerformance(Request $request, array $filters): array
    {
        // Get business-scoped departments
        $employeeQuery = $this->getBusinessScopedEmployees($request);
        $departments = $employeeQuery->select('department')->distinct()->get()->pluck('department');
        
        return $departments->map(function ($department) use ($request, $filters) {
            if (empty($department)) return null;
            
            // Get business-scoped employees for this department
            $employees = $this->getBusinessScopedEmployees($request)
                ->where('department', $department)
                ->get();
                
            $employeeCount = $employees->count();
            if ($employeeCount === 0) {
                return [
                    'department' => $department,
                    'employee_count' => 0,
                    'attendance_rate' => 0,
                    'productivity_score' => 0,
                    'leave_utilization' => 0,
                ];
            }
            
            $employeeIds = $employees->pluck('id');
            
            $attendanceQuery = Attendance::whereIn('employee_id', $employeeIds);
            if (isset($filters['start_date'])) {
                $attendanceQuery->where('date', '>=', $filters['start_date']);
            }
            if (isset($filters['end_date'])) {
                $attendanceQuery->where('date', '<=', $filters['end_date']);
            }
            $totalAttendance = $attendanceQuery->count();
            $presentAttendance = $attendanceQuery->where('status', 'present')->count();
            $attendanceRate = $totalAttendance > 0 ? ($presentAttendance / $totalAttendance) * 100 : 0;
            
            $leaveQuery = Leave::whereIn('employee_id', $employeeIds)
                ->where('status', 'approved');
            
            if (isset($filters['start_date'])) {
                $leaveQuery->where('start_date', '>=', $filters['start_date']);
            }
            if (isset($filters['end_date'])) {
                $leaveQuery->where('end_date', '<=', $filters['end_date']);
            }
            $totalLeaveDays = $leaveQuery->sum('total_days');
            $maxPossibleLeaveDays = $employeeCount * 30;
            $leaveUtilization = $maxPossibleLeaveDays > 0 ? ($totalLeaveDays / $maxPossibleLeaveDays) * 100 : 0;
            
            return [
                'department' => $department,
                'employee_count' => $employeeCount,
                'attendance_rate' => round($attendanceRate, 2),
                'productivity_score' => round($attendanceRate * 0.8),
                'leave_utilization' => round($leaveUtilization, 2),
                'active_employees' => $employeeCount,
            ];
        })->filter()->values()->toArray();
    }

    public function exportReport(Request $request, string $type)
    {
        $validated = $request->validate([
            'format' => 'required|in:pdf,csv',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department' => 'sometimes|string',
            'status' => 'sometimes|in:all,paid,pending,approved,rejected',
        ]);

        try {
            $filters = $validated;
            $filters['request'] = $request;
            $format = $validated['format'];
            
            switch ($type) {
                case 'payroll':
                    $reportData = $this->generatePayrollReport(new Request($filters))->getData(true);
                    $filename = "payroll_report_" . now()->format('Y-m-d_His') . "." . $format;
                    $view = 'reports.payroll';
                    break;
                  
                case 'attendance':
                    $reportData = $this->generateAttendanceReport(new Request($filters))->getData(true);
                    $filename = "attendance_report_" . now()->format('Y-m-d_His') . "." . $format;
                    $view = 'reports.attendance';
                    break;
                  
                case 'leave':
                    $reportData = $this->generateLeaveReport(new Request($filters))->getData(true);
                    $filename = "leave_report_" . now()->format('Y-m-d_His') . "." . $format;
                    $view = 'reports.leave';
                    break;
                  
                case 'productivity':
                    $reportData = $this->generateProductivityReport(new Request($filters))->getData(true);
                    $filename = "productivity_report_" . now()->format('Y-m-d_His') . "." . $format;
                    $view = 'reports.productivity';
                    break;
                  
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid report type'
                    ], 422);
            }

            Log::info("Generating {$type} report", [
                'format' => $format,
                'filters' => $filters,
                'data_count' => count($reportData['data'] ?? [])
            ]);

            if ($format === 'pdf') {
                return $this->reportExportService->exportToPdf($view, $reportData, $filename);
            } else {
                return $this->reportExportService->exportToCsv($reportData['data'] ?? [], $filename);
            }

        } catch (\Exception $e) {
            Log::error("Export {$type} report error: " . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to export report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to get payroll report data without JSON response wrapper
     */
    private function generatePayrollReportData(Request $request, array $filters): array
    {
        // Get business-scoped employee IDs
        $employeeQuery = $this->getBusinessScopedEmployees($request);
        if (isset($filters['department'])) {
            $employeeQuery->where('department', $filters['department']);
        }
        $employeeIds = $employeeQuery->pluck('id');
        
        $query = Payslip::with('employee')
            ->whereIn('employee_id', $employeeIds);
            
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }
        $query->where('pay_period_start', '>=', $filters['start_date'])
              ->where('pay_period_end', '<=', $filters['end_date']);
        $payslips = $query->get();
        $processedEmployees = $payslips->count();
        $totalNetSalary = $payslips->sum('net_pay');
        $totalTaxAmount = $payslips->sum('paye') + $payslips->sum('napsa') + $payslips->sum('nhima');
        $averageNetSalary = $processedEmployees > 0 ? round($totalNetSalary / $processedEmployees, 2) : 0;
        
        // Get currency from first payslip if available
        $currency = null;
        $currencySymbol = null;
        if ($payslips->isNotEmpty() && $payslips->first()->employee && $payslips->first()->employee->country) {
            $currency = $payslips->first()->employee->country->currency;
            $currencySymbol = $payslips->first()->employee->country->currency_symbol;
        }
        
        return [
            'period_start' => $filters['start_date'],
            'period_end' => $filters['end_date'],
            'processed_employees' => $processedEmployees,
            'total_net_salary' => $totalNetSalary,
            'average_net_salary' => $averageNetSalary,
            'total_tax_amount' => $totalTaxAmount,
            'currency' => $currency,
            'currency_symbol' => $currencySymbol,
            'payslip_details' => $payslips->map(function ($payslip) {
                $startDate = Carbon::parse($payslip->pay_period_start)->format('M d, Y');
                $endDate = Carbon::parse($payslip->pay_period_end)->format('M d, Y');
                return [
                    'employee_id' => $payslip->employee_id,
                    'employee_name' => $payslip->employee->user ? ($payslip->employee->user->first_name . ' ' . $payslip->employee->user->last_name) : 'N/A',
                    'gross_salary' => $payslip->gross_salary ?? 0,
                    'deductions' => $payslip->total_deductions ?? 0,
                    'net_salary' => $payslip->net_pay ?? 0,
                    'tax_amount' => ($payslip->paye ?? 0) + ($payslip->napsa ?? 0) + ($payslip->nhima ?? 0),
                    'pay_period' => $startDate . ' to ' . $endDate,
                ];
            })->toArray(),
        ];
    }

    /**
     * Generate Payroll CSV in the exact template format from the shared document
     */
    private function generatePayrollCsvTemplate(Request $request, array $filters): string
    {
        $csv = "BInSol - U ver 1.00,,,,,,,, \n";
        $csv .= now()->format('m/d/Y') . ",,,,,,,,, \n";
        $csv .= "62000031451,1.23457E+11,,,,,,, \n";
        $csv .= "RECIPIENT NAME,RECIPIENT ACCOUNT,RECIPIENT ACCOUNT TYPE,BRANCHCODE,AMOUNT,OWN REFERENCE,RECIPIENT REFERENCE,EMAIL 1 NOTIFY,EMAIL 1 ADDRESS\n";
        
        // Get business-scoped employee IDs
        $employeeQuery = $this->getBusinessScopedEmployees($request);
        if (isset($filters['department'])) {
            $employeeQuery->where('department', $filters['department']);
        }
        $employeeIds = $employeeQuery->pluck('id');
        
        $query = Payslip::with('employee')
            ->whereIn('employee_id', $employeeIds);
            
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }
        $query->where('pay_period_start', '>=', $filters['start_date'])
              ->where('pay_period_end', '<=', $filters['end_date']);
        $payslips = $query->get();
        
        foreach ($payslips as $payslip) {
            $employee = $payslip->employee;
            $row = [
                '"' . ($employee->user ? ($employee->user->first_name . ' ' . $employee->user->last_name) : 'N/A') . '"',
                $employee->bank_account ?? '',
                $employee->account_type ?? 'Checking',
                $employee->branch_code ?? '',
                number_format($payslip->net_pay ?? 0, 2, '.', ''),
                'Payroll-' . ($payslip->payroll_id ?? $payslip->id),
                $employee->id,
                'Y',
                '"' . ($employee->user?->email ?? '') . '"',
            ];
            $csv .= implode(',', $row) . "\n";
        }
        return $csv;
    }

    /**
     * Extract deductions data from payslip with dynamic names
     */
    private function extractDeductionsData(Payslip $payslip, array $statutoryDeductions): array
    {
        $deductions = [];
        
        // Extract PAYE tax
        if ($payslip->paye > 0) {
            $deductions[] = [
                'name' => 'PAYE Tax',
                'amount' => $payslip->paye,
                'type' => 'statutory',
                'description' => 'Pay As You Earn Tax'
            ];
        }
        
        // Extract statutory deductions dynamically
        foreach ($statutoryDeductions as $deductionConfig) {
            $deductionName = $deductionConfig['name'] ?? '';
            $deductionType = $deductionConfig['type'] ?? 'statutory';
            
            if (!empty($deductionName)) {
                // Map deduction names to payslip fields
                $fieldName = $this->mapDeductionToField($deductionName);
                $amount = $payslip->{$fieldName} ?? 0;
                
                if ($amount > 0) {
                    $deductions[] = [
                        'name' => $deductionName,
                        'amount' => $amount,
                        'type' => $deductionType,
                        'description' => $deductionConfig['description'] ?? $deductionName
                    ];
                }
            }
        }
        
        // Extract other deductions (voluntary, loans, etc.)
        $otherDeductions = $payslip->other_deductions ?? 0;
        if ($otherDeductions > 0) {
            // Try to parse detailed other deductions from JSON
            $detailedOtherDeductions = json_decode($payslip->other_deductions_breakdown ?? '[]', true);
            
            if (is_array($detailedOtherDeductions) && count($detailedOtherDeductions) > 0) {
                foreach ($detailedOtherDeductions as $deduction) {
                    $deductions[] = [
                        'name' => $deduction['name'] ?? 'Other Deduction',
                        'amount' => $deduction['amount'] ?? 0,
                        'type' => $deduction['type'] ?? 'voluntary',
                        'description' => $deduction['description'] ?? 'Additional deduction'
                    ];
                }
            } else {
                // Fallback to lump sum
                $deductions[] = [
                    'name' => 'Other Deductions',
                    'amount' => $otherDeductions,
                    'type' => 'voluntary',
                    'description' => 'Miscellaneous deductions'
                ];
            }
        }
        
        return $deductions;
    }

    /**
     * Map deduction names to database field names
     */
    private function mapDeductionToField(string $deductionName): string
    {
        $mapping = [
            'NAPSA' => 'napsa',
            'NHIMA' => 'nhima',
            'Pension' => 'pension',
            'Social Security' => 'social_security',
            'Provident Fund' => 'provident_fund',
            'Health Insurance' => 'health_insurance',
            'Union Dues' => 'union_dues'
        ];
        
        // Try exact match
        if (isset($mapping[$deductionName])) {
            return $mapping[$deductionName];
        }
        
        // Try case-insensitive match
        $lowerName = strtolower($deductionName);
        foreach ($mapping as $key => $field) {
            if (strtolower($key) === $lowerName) {
                return $field;
            }
        }
        
        // Default: convert to snake_case
        return strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', $deductionName));
    }
}