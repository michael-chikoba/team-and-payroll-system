<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class ReportExportService
{
    /**
     * Export report to PDF
     */
    public function exportToPdf(string $view, array $data, string $filename)
    {
        try {
            Log::info('EXPORT_SERVICE: Starting PDF generation', [
                'view' => $view,
                'filename' => $filename,
                'data_keys' => array_keys($data)
            ]);

            // Prepare the data structure for the view
            $reportData = $this->prepareDataForPdf($data);

            Log::info('EXPORT_SERVICE: Data prepared for PDF', [
                'report_keys' => array_keys($reportData),
                'has_summary' => isset($reportData['summary']),
                'has_data' => isset($reportData['data']),
                'processed_employees' => $reportData['processed_employees'] ?? 0
            ]);

            // Generate PDF with the report data
            $pdf = Pdf::loadView($view, ['report' => $reportData])
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => false,
                    'defaultFont' => 'sans-serif'
                ]);

            Log::info('EXPORT_SERVICE: PDF generated successfully');

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('EXPORT_SERVICE: PDF Generation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'view' => $view,
                'filename' => $filename
            ]);
            
            throw $e;
        }
    }

    /**
     * Prepare data structure for PDF views.
     *
     * Currency, country, and date values are passed through strictly from the
     * report data. No defaults are injected — null means "not available" and
     * the Blade view must handle it gracefully (e.g. show "N/A", omit the
     * symbol, or label the report "Multi-currency").
     */
    private function prepareDataForPdf(array $data): array
    {
        Log::info('EXPORT_SERVICE: Preparing data structure', [
            'input_keys' => array_keys($data),
            'has_success' => isset($data['success']),
            'has_data' => isset($data['data']),
            'has_summary' => isset($data['summary'])
        ]);

        $preparedData = [];

        // Scenario 1: Data wrapped in success/data structure from controller
        if (isset($data['success']) && isset($data['data']) && is_array($data['data'])) {
            Log::info('EXPORT_SERVICE: Processing controller response structure');
            $preparedData = $data['data'];
        }
        // Scenario 2: Data from service with summary and data arrays
        elseif (isset($data['summary']) && isset($data['data'])) {
            Log::info('EXPORT_SERVICE: Processing service response structure');
            
            // Merge summary with payslip details
            $preparedData = $data['summary'];
            
            // Add the detailed data based on report type
            if (is_object($data['data']) && method_exists($data['data'], 'toArray')) {
                $preparedData['payslip_details'] = $data['data']->toArray();
            } elseif (is_array($data['data'])) {
                $preparedData['payslip_details'] = $data['data'];
            } else {
                $preparedData['payslip_details'] = [];
            }
            
            $preparedData['generated_at'] = $data['generated_at'] ?? now();
        }
        // Scenario 3: Already prepared flat structure
        else {
            Log::info('EXPORT_SERVICE: Using flat data structure');
            $preparedData = $data;
        }

        // Period dates — resolve from data only; null if genuinely absent.
        // Never invent a date range (e.g. current month) as that would silently
        // misrepresent what the report covers.
        if (!isset($preparedData['period_start'])) {
            $preparedData['period_start'] = $preparedData['start_date']
                ?? $preparedData['filters']['start_date']
                ?? null;
        }

        if (!isset($preparedData['period_end'])) {
            $preparedData['period_end'] = $preparedData['end_date']
                ?? $preparedData['filters']['end_date']
                ?? null;
        }

        // Currency — pass through exactly what the report resolved.
        // null means the result set spans multiple currencies or has no country
        // data; the view should handle this case explicitly.
        if (!isset($preparedData['currency'])) {
            $preparedData['currency'] = $preparedData['filters']['currency'] ?? null;
        }

        if (!isset($preparedData['currency_symbol'])) {
            $preparedData['currency_symbol'] = $preparedData['filters']['currency_symbol'] ?? null;
        }

        // Department — null when not filtered; the view should show "All Departments"
        // or similar copy itself rather than having this service invent it.
        if (!isset($preparedData['department'])) {
            $preparedData['department'] = $preparedData['filters']['department'] ?? null;
        }

        // CRITICAL FIX: Ensure all numeric fields are actually numeric, not strings
        $numericFields = [
            'total_paye_tax',
            'total_paye',
            'total_tax_withheld',
            'total_earnings',
            'total_gross_salary',
            'total_deductions',
            'total_all_deductions',
            'average_earnings',
            'average_deductions',
            'average_gross_salary',
            'average_net_salary',
            'processed_employees'
        ];

        foreach ($numericFields as $field) {
            if (isset($preparedData[$field])) {
                $value = $preparedData[$field];
                if (is_string($value)) {
                    $value = str_replace(',', '', $value);
                }
                $preparedData[$field] = (float) $value;
            }
        }

        // Map common field variations with proper type conversion
        $preparedData['total_paye_tax'] = $this->ensureNumeric(
            $preparedData['total_paye_tax'] ?? 
            $preparedData['total_paye'] ?? 
            $preparedData['total_tax_withheld'] ?? 
            null
        );

        $preparedData['total_earnings'] = $this->ensureNumeric(
            $preparedData['total_earnings'] ?? 
            $preparedData['total_gross_salary'] ?? 
            null
        );

        $preparedData['total_deductions'] = $this->ensureNumeric(
            $preparedData['total_all_deductions'] ?? 
            $preparedData['total_deductions'] ?? 
            null
        );

        // Ensure processed_employees exists
        if (!isset($preparedData['processed_employees'])) {
            $preparedData['processed_employees'] = count($preparedData['payslip_details'] ?? []);
        }

        // Ensure average fields exist with proper numeric types
        if (!isset($preparedData['average_deductions']) && $preparedData['processed_employees'] > 0) {
            $preparedData['average_deductions'] = $preparedData['total_deductions'] / $preparedData['processed_employees'];
        } else {
            $preparedData['average_deductions'] = $this->ensureNumeric($preparedData['average_deductions'] ?? null);
        }

        if (!isset($preparedData['average_earnings']) && $preparedData['processed_employees'] > 0) {
            $preparedData['average_earnings'] = $preparedData['total_earnings'] / $preparedData['processed_employees'];
        } else {
            $preparedData['average_earnings'] = $this->ensureNumeric($preparedData['average_earnings'] ?? null);
        }

        // Clean up employee earnings/deductions data
        if (isset($preparedData['employee_earnings']) && is_array($preparedData['employee_earnings'])) {
            $preparedData['employee_earnings'] = $this->cleanEmployeeData($preparedData['employee_earnings']);
        }

        if (isset($preparedData['payslip_details']) && is_array($preparedData['payslip_details'])) {
            $preparedData['payslip_details'] = $this->cleanEmployeeData($preparedData['payslip_details']);
        }

        if (isset($preparedData['employee_deductions']) && is_array($preparedData['employee_deductions'])) {
            $preparedData['employee_deductions'] = $this->cleanEmployeeData($preparedData['employee_deductions']);
        }

        // Map deductions data for deductions report
        if (!isset($preparedData['employee_deductions']) && isset($preparedData['payslip_details'])) {
            $preparedData['employee_deductions'] = $preparedData['payslip_details'];
        }

        // Map earnings data for earnings report
        if (!isset($preparedData['employee_earnings']) && isset($preparedData['payslip_details'])) {
            $preparedData['employee_earnings'] = $preparedData['payslip_details'];
        }

        // Clean earning_breakdown and deduction_breakdown arrays
        if (isset($preparedData['earning_breakdown']) && is_array($preparedData['earning_breakdown'])) {
            $preparedData['earning_breakdown'] = $this->cleanBreakdownData($preparedData['earning_breakdown']);
        }

        if (isset($preparedData['deduction_breakdown']) && is_array($preparedData['deduction_breakdown'])) {
            $preparedData['deduction_breakdown'] = $this->cleanBreakdownData($preparedData['deduction_breakdown']);
        }

        Log::info('EXPORT_SERVICE: Data preparation complete', [
            'output_keys' => array_keys($preparedData),
            'period_start' => $preparedData['period_start'],
            'period_end' => $preparedData['period_end'],
            'currency' => $preparedData['currency'],
            'currency_symbol' => $preparedData['currency_symbol'],
            'is_multi_currency' => $preparedData['is_multi_currency'] ?? null,
            'processed_employees' => $preparedData['processed_employees'],
            'total_deductions' => $preparedData['total_deductions'],
            'has_payslip_details' => isset($preparedData['payslip_details']),
            'payslip_count' => count($preparedData['payslip_details'] ?? [])
        ]);

        return $preparedData;
    }

    /**
     * Ensure a value is numeric (float).
     * Returns 0.0 for null/unparseable values so arithmetic in views is safe,
     * but callers that need to distinguish "zero" from "absent" should check
     * for null before calling this method.
     */
    private function ensureNumeric($value): float
    {
        if (is_null($value)) {
            return 0.0;
        }
        
        if (is_numeric($value)) {
            return (float) $value;
        }
        
        if (is_string($value)) {
            $cleaned = str_replace(',', '', trim($value));
            if (is_numeric($cleaned)) {
                return (float) $cleaned;
            }
        }
        
        return 0.0;
    }

    /**
     * Clean employee data arrays to ensure all numeric fields are proper floats
     */
    private function cleanEmployeeData(array $employees): array
    {
        return array_map(function ($employee) {
            $numericFields = [
                'total_earnings',
                'gross_salary',
                'total_deductions',
                'net_salary',
                'paye_tax'
            ];

            foreach ($numericFields as $field) {
                if (isset($employee[$field])) {
                    $employee[$field] = $this->ensureNumeric($employee[$field]);
                }
            }

            // Clean earnings_breakdown
            if (isset($employee['earnings_breakdown']) && is_array($employee['earnings_breakdown'])) {
                foreach ($employee['earnings_breakdown'] as $key => $value) {
                    $employee['earnings_breakdown'][$key] = $this->ensureNumeric($value);
                }
            }

            // Clean deductions_breakdown
            if (isset($employee['deductions_breakdown']) && is_array($employee['deductions_breakdown'])) {
                foreach ($employee['deductions_breakdown'] as $key => $value) {
                    $employee['deductions_breakdown'][$key] = $this->ensureNumeric($value);
                }
            }

            return $employee;
        }, $employees);
    }

    /**
     * Clean breakdown data arrays
     */
    private function cleanBreakdownData(array $breakdown): array
    {
        return array_map(function ($item) {
            if (isset($item['total_amount'])) {
                $item['total_amount'] = $this->ensureNumeric($item['total_amount']);
            }
            if (isset($item['employee_count'])) {
                $item['employee_count'] = (int) $item['employee_count'];
            }
            return $item;
        }, $breakdown);
    }

    /**
     * Export report to CSV
     */
    public function exportToCsv(array $data, string $filename, string $reportType = 'payroll')
    {
        $callback = function() use ($data, $reportType) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for UTF-8

            Log::info('EXPORT_SERVICE: Starting CSV generation', [
                'report_type' => $reportType,
                'data_structure' => array_keys($data),
                'has_summary' => isset($data['summary']),
                'has_payslip_details' => isset($data['payslip_details']),
                'has_earning_headers' => isset($data['earning_headers'])
            ]);

            $reportData = $this->extractReportDataForCsv($data, $reportType);
            
            switch ($reportType) {
                case 'payroll':
                    $this->generateDetailedPayrollCsv($file, $reportData);
                    break;
                case 'earnings':
                    $this->generateEarningsCsv($file, $reportData);
                    break;
                case 'deductions':
                    $this->generateDeductionsCsv($file, $reportData);
                    break;
                default:
                    $this->generateSimpleCsv($file, $reportData['data'] ?? []);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Extract and prepare data for CSV generation
     */
    private function extractReportDataForCsv(array $data, string $reportType): array
    {
        $result = [];
        
        if (isset($data['data']) && is_array($data['data'])) {
            $result = $data['data'];
        } else {
            $result = $data;
        }

        if (!isset($result['payslip_details']) && isset($result['data']) && is_array($result['data'])) {
            $result['payslip_details'] = $result['data'];
        }

        return $result;
    }

    /**
     * Generate Detailed Payroll CSV with all earnings and deductions
     */
    private function generateDetailedPayrollCsv($file, array $data): void
    {
        Log::info('EXPORT_SERVICE: Generating detailed payroll CSV', [
            'has_payslip_details' => isset($data['payslip_details']),
            'payslip_count' => count($data['payslip_details'] ?? []),
            'has_earning_headers' => isset($data['earning_headers']),
            'has_deduction_headers' => isset($data['deduction_headers'])
        ]);

        $earningHeaders = $data['earning_headers'] ?? [];
        $deductionHeaders = $data['deduction_headers'] ?? [];
        
        if (empty($earningHeaders) && !empty($data['payslip_details'])) {
            $firstPayslip = $data['payslip_details'][0] ?? [];
            if (isset($firstPayslip['earnings_breakdown'])) {
                $earningHeaders = array_keys($firstPayslip['earnings_breakdown']);
            }
        }
        
        if (empty($deductionHeaders) && !empty($data['payslip_details'])) {
            $firstPayslip = $data['payslip_details'][0] ?? [];
            if (isset($firstPayslip['deductions_breakdown'])) {
                $deductionHeaders = array_keys($firstPayslip['deductions_breakdown']);
            }
        }

        $csvHeaders = [
            'Employee Name',
            'Employee ID', 
            'Business',
            'Country',
            'Department',
            'Pay Period',
            'Period Start',
            'Period End',
            'Status'
        ];

        $csvHeaders[] = 'Gross Salary';
        foreach ($earningHeaders as $header) {
            $csvHeaders[] = $header . ' (Earnings)';
        }
        $csvHeaders[] = 'Total Earnings';

        foreach ($deductionHeaders as $header) {
            $csvHeaders[] = $header . ' (Deductions)';
        }
        $csvHeaders[] = 'Total Deductions';
        $csvHeaders[] = 'PAYE Tax';
        $csvHeaders[] = 'Net Salary';

        fputcsv($file, $csvHeaders);

        $payslips = $data['payslip_details'] ?? [];
        
        foreach ($payslips as $row) {
            $csvRow = [
                $row['employee_name'] ?? 'N/A',
                $row['employee_id'] ?? '',
                $row['business'] ?? 'N/A',
                $row['country'] ?? 'N/A',
                $row['country_code'] ?? 'N/A',
                $row['department'] ?? 'N/A',
                $row['pay_period'] ?? 'N/A',
                $row['pay_period_start'] ?? 'N/A',
                $row['pay_period_end'] ?? 'N/A',
                $row['status'] ?? 'N/A'
            ];

            $csvRow[] = $this->ensureNumeric($row['gross_salary'] ?? 0);

            $totalEarnings = 0;
            foreach ($earningHeaders as $header) {
                $amount = $this->ensureNumeric($row['earnings_breakdown'][$header] ?? 0);
                $csvRow[] = $amount;
                $totalEarnings += $amount;
            }
            $csvRow[] = $this->ensureNumeric($row['total_earnings'] ?? $totalEarnings);

            $totalDeductions = 0;
            foreach ($deductionHeaders as $header) {
                $amount = $this->ensureNumeric($row['deductions_breakdown'][$header] ?? 0);
                $csvRow[] = $amount;
                $totalDeductions += $amount;
            }
            $csvRow[] = $this->ensureNumeric($row['total_deductions'] ?? $totalDeductions);
            $csvRow[] = $this->ensureNumeric($row['paye_tax'] ?? 0);
            $csvRow[] = $this->ensureNumeric($row['net_salary'] ?? 0);

            fputcsv($file, $csvRow);
        }

        if (isset($data['summary']) || isset($data['total_gross_salary'])) {
            fputcsv($file, []); // Empty row
            fputcsv($file, ['SUMMARY', '', '', '', '', '', '', '', '']);
            fputcsv($file, ['Total Employees Processed:', $data['processed_employees'] ?? count($payslips)]);
            fputcsv($file, ['Total Gross Salary:', $this->ensureNumeric($data['total_gross_salary'] ?? 0)]);
            fputcsv($file, ['Total Net Salary:', $this->ensureNumeric($data['total_net_salary'] ?? 0)]);
            fputcsv($file, ['Total Earnings:', $this->ensureNumeric($data['total_earnings'] ?? 0)]);
            fputcsv($file, ['Total Deductions:', $this->ensureNumeric($data['total_deductions'] ?? $data['total_all_deductions'] ?? 0)]);
            fputcsv($file, ['Total PAYE Tax:', $this->ensureNumeric($data['total_paye_tax'] ?? 0)]);
        }
    }

    /**
     * Generate Earnings CSV
     */
    private function generateEarningsCsv($file, array $data): void
    {
        Log::info('EXPORT_SERVICE: Generating earnings CSV');
        
        $earningHeaders = $data['earning_headers'] ?? [];
        
        if (empty($earningHeaders) && !empty($data['employee_earnings'])) {
            $firstEmployee = $data['employee_earnings'][0] ?? [];
            if (isset($firstEmployee['earnings_breakdown'])) {
                $earningHeaders = array_keys($firstEmployee['earnings_breakdown']);
            }
        }

        $csvHeaders = [
            'Employee Name',
            'Employee ID',
            'Business',
            'Country',
            'Department',
            'Pay Period',
            'Period Start',
            'Period End',
            'Status'
        ];

        foreach ($earningHeaders as $header) {
            $csvHeaders[] = $header;
        }
        
        $csvHeaders[] = 'Total Earnings';
        $csvHeaders[] = 'Gross Salary';

        fputcsv($file, $csvHeaders);

        $employees = $data['employee_earnings'] ?? $data['payslip_details'] ?? [];
        
        foreach ($employees as $row) {
            $csvRow = [
                $row['employee_name'] ?? 'N/A',
                $row['employee_id'] ?? '',
                $row['business'] ?? 'N/A',
                $row['country'] ?? 'N/A',
                $row['department'] ?? 'N/A',
                $row['pay_period'] ?? 'N/A',
                $row['pay_period_start'] ?? 'N/A',
                $row['pay_period_end'] ?? 'N/A',
                $row['status'] ?? 'N/A'
            ];

            $totalEarnings = 0;
            foreach ($earningHeaders as $header) {
                $amount = $this->ensureNumeric($row['earnings_breakdown'][$header] ?? 0);
                $csvRow[] = $amount;
                $totalEarnings += $amount;
            }
            
            $csvRow[] = $this->ensureNumeric($row['total_earnings'] ?? $totalEarnings);
            $csvRow[] = $this->ensureNumeric($row['gross_salary'] ?? 0);

            fputcsv($file, $csvRow);
        }
    }

    /**
     * Generate Deductions CSV
     */
    private function generateDeductionsCsv($file, array $data): void
    {
        Log::info('EXPORT_SERVICE: Generating deductions CSV');
        
        $deductionHeaders = $data['deduction_headers'] ?? [];
        
        if (empty($deductionHeaders) && !empty($data['employee_deductions'])) {
            $firstEmployee = $data['employee_deductions'][0] ?? [];
            if (isset($firstEmployee['deductions_breakdown'])) {
                $deductionHeaders = array_keys($firstEmployee['deductions_breakdown']);
            }
        }

        $csvHeaders = [
            'Employee Name',
            'Employee ID',
            'Business',
            'Country',
            'Department',
            'Pay Period',
            'Period Start',
            'Period End',
            'Status'
        ];

        foreach ($deductionHeaders as $header) {
            $csvHeaders[] = $header;
        }
        
        $csvHeaders[] = 'Total Deductions';
        $csvHeaders[] = 'PAYE Tax';
        $csvHeaders[] = 'Net Salary';

        fputcsv($file, $csvHeaders);

        $employees = $data['employee_deductions'] ?? $data['payslip_details'] ?? [];
        
        foreach ($employees as $row) {
            $csvRow = [
                $row['employee_name'] ?? 'N/A',
                $row['employee_id'] ?? '',
                $row['business'] ?? 'N/A',
                $row['country'] ?? 'N/A',
                $row['department'] ?? 'N/A',
                $row['pay_period'] ?? 'N/A',
                $row['pay_period_start'] ?? 'N/A',
                $row['pay_period_end'] ?? 'N/A',
                $row['status'] ?? 'N/A'
            ];

            $totalDeductions = 0;
            foreach ($deductionHeaders as $header) {
                $amount = $this->ensureNumeric($row['deductions_breakdown'][$header] ?? 0);
                $csvRow[] = $amount;
                $totalDeductions += $amount;
            }
            
            $csvRow[] = $this->ensureNumeric($row['total_deductions'] ?? $totalDeductions);
            $csvRow[] = $this->ensureNumeric($row['paye_tax'] ?? 0);
            $csvRow[] = $this->ensureNumeric($row['net_salary'] ?? 0);

            fputcsv($file, $csvRow);
        }
    }

    /**
     * Generate simple CSV for basic data arrays
     */
    private function generateSimpleCsv($file, array $data): void
    {
        if (empty($data)) {
            fputcsv($file, ['No data available']);
            return;
        }

        $headers = array_keys($data[0]);
        fputcsv($file, array_map(fn($h) => str_replace('_', ' ', ucwords($h)), $headers));
        
        foreach ($data as $row) {
            fputcsv($file, $row);
        }
    }
}