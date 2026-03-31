<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class ReportExportService
{
    // ======================================================================
    // PDF export
    // ======================================================================

    /**
     * Export report to PDF.
     *
     * Currency and country values are passed through as-is from the report data.
     * No defaults are injected — the Blade view is responsible for handling null
     * values gracefully (e.g. showing "Multi-currency" or omitting the symbol).
     */
    public function exportToPdf(string $view, array $data, string $filename)
    {
        try {
            Log::info('EXPORT_SERVICE: Starting PDF generation', [
                'view'      => $view,
                'filename'  => $filename,
                'data_keys' => array_keys($data),
            ]);

            $reportData = $this->prepareDataForPdf($data);

            Log::info('EXPORT_SERVICE: Data prepared for PDF', [
                'report_keys'        => array_keys($reportData),
                'processed_employees' => $reportData['processed_employees'] ?? 0,
                'currency'           => $reportData['currency']            ?? null,
                'is_multi_currency'  => $reportData['is_multi_currency']   ?? null,
            ]);

            $pdf = Pdf::loadView($view, ['report' => $reportData])
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled'      => false,
                    'defaultFont'          => 'sans-serif',
                ]);

            Log::info('EXPORT_SERVICE: PDF generated successfully');

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('EXPORT_SERVICE: PDF Generation Error', [
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
                'view'     => $view,
                'filename' => $filename,
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
            'input_keys'  => array_keys($data),
            'has_success' => isset($data['success']),
            'has_data'    => isset($data['data']),
            'has_summary' => isset($data['summary']),
        ]);

        $preparedData = [];

        // Scenario 1: Controller JSON wrapper  { success, data, type }
        if (isset($data['success']) && isset($data['data']) && is_array($data['data'])) {
            Log::info('EXPORT_SERVICE: Processing controller response structure');
            $preparedData = $data['data'];
        }
        // Scenario 2: Service output  { summary, data, generated_at }
        elseif (isset($data['summary']) && isset($data['data'])) {
            Log::info('EXPORT_SERVICE: Processing service response structure');
            $preparedData = $data['summary'];

            $rawData = $data['data'];
            if (is_object($rawData) && method_exists($rawData, 'toArray')) {
                $preparedData['payslip_details'] = $rawData->toArray();
            } elseif (is_array($rawData)) {
                $preparedData['payslip_details'] = $rawData;
            } else {
                $preparedData['payslip_details'] = [];
            }

            $preparedData['generated_at'] = $data['generated_at'] ?? now();
        }
        // Scenario 3: Already flat
        else {
            Log::info('EXPORT_SERVICE: Using flat data structure');
            $preparedData = $data;
        }

        // ------------------------------------------------------------------
        // Period dates — resolve from data only; null if genuinely absent.
        // ------------------------------------------------------------------
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

        // ------------------------------------------------------------------
        // Currency — pass through exactly what the report resolved.
        // null means multi-currency or no country data; the view handles it.
        // ------------------------------------------------------------------
        if (!isset($preparedData['currency'])) {
            $preparedData['currency'] = $preparedData['filters']['currency'] ?? null;
        }

        if (!isset($preparedData['currency_symbol'])) {
            $preparedData['currency_symbol'] = $preparedData['filters']['currency_symbol'] ?? null;
        }

        // Department — null when not filtered
        if (!isset($preparedData['department'])) {
            $preparedData['department'] = $preparedData['filters']['department'] ?? null;
        }

        // ------------------------------------------------------------------
        // Ensure all numeric fields are actually numeric (not strings)
        // ------------------------------------------------------------------
        $numericFields = [
            'total_paye_tax',
            'total_paye',
            'total_tax_withheld',
            'total_earnings',
            'total_gross_salary',
            'total_deductions',
            'total_all_deductions',
            'total_net_salary',
            'average_earnings',
            'average_deductions',
            'average_gross_salary',
            'average_net_salary',
            'processed_employees',
        ];

        foreach ($numericFields as $field) {
            if (isset($preparedData[$field])) {
                $preparedData[$field] = $this->ensureNumeric($preparedData[$field]);
            }
        }

        // Normalise aliased field names
        $preparedData['total_paye_tax'] = $this->ensureNumeric(
            $preparedData['total_paye_tax']    ??
            $preparedData['total_paye']        ??
            $preparedData['total_tax_withheld'] ??
            null
        );

        $preparedData['total_earnings'] = $this->ensureNumeric(
            $preparedData['total_earnings']    ??
            $preparedData['total_gross_salary'] ??
            null
        );

        $preparedData['total_deductions'] = $this->ensureNumeric(
            $preparedData['total_all_deductions'] ??
            $preparedData['total_deductions']     ??
            null
        );

        // Ensure processed_employees
        if (!isset($preparedData['processed_employees'])) {
            $preparedData['processed_employees'] = count($preparedData['payslip_details'] ?? []);
        }

        // Average deductions
        if (!isset($preparedData['average_deductions']) || $preparedData['average_deductions'] === null) {
            $preparedData['average_deductions'] = $preparedData['processed_employees'] > 0
                ? $preparedData['total_deductions'] / $preparedData['processed_employees']
                : 0.0;
        } else {
            $preparedData['average_deductions'] = $this->ensureNumeric($preparedData['average_deductions']);
        }

        // Average earnings
        if (!isset($preparedData['average_earnings']) || $preparedData['average_earnings'] === null) {
            $preparedData['average_earnings'] = $preparedData['processed_employees'] > 0
                ? $preparedData['total_earnings'] / $preparedData['processed_employees']
                : 0.0;
        } else {
            $preparedData['average_earnings'] = $this->ensureNumeric($preparedData['average_earnings']);
        }

        // ------------------------------------------------------------------
        // Clean individual employee rows
        // ------------------------------------------------------------------
        foreach (['employee_earnings', 'payslip_details', 'employee_deductions'] as $key) {
            if (isset($preparedData[$key]) && is_array($preparedData[$key])) {
                $preparedData[$key] = $this->cleanEmployeeData($preparedData[$key]);
            }
        }

        // Cross-populate employee data aliases
        if (!isset($preparedData['employee_deductions']) && isset($preparedData['payslip_details'])) {
            $preparedData['employee_deductions'] = $preparedData['payslip_details'];
        }
        if (!isset($preparedData['employee_earnings']) && isset($preparedData['payslip_details'])) {
            $preparedData['employee_earnings'] = $preparedData['payslip_details'];
        }

        // Clean breakdown arrays
        foreach (['earning_breakdown', 'deduction_breakdown'] as $key) {
            if (isset($preparedData[$key]) && is_array($preparedData[$key])) {
                $preparedData[$key] = $this->cleanBreakdownData($preparedData[$key]);
            }
        }

        Log::info('EXPORT_SERVICE: Data preparation complete', [
            'output_keys'        => array_keys($preparedData),
            'period_start'       => $preparedData['period_start'],
            'period_end'         => $preparedData['period_end'],
            'currency'           => $preparedData['currency'],
            'currency_symbol'    => $preparedData['currency_symbol'],
            'is_multi_currency'  => $preparedData['is_multi_currency'] ?? null,
            'processed_employees' => $preparedData['processed_employees'],
            'payslip_count'      => count($preparedData['payslip_details'] ?? []),
        ]);

        return $preparedData;
    }

    /**
     * Ensure a value is a float.  Returns 0.0 for null / unparseable values.
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
     * Clean employee data arrays — ensure all numeric fields are proper floats.
     * Per-row currency fields are passed through untouched.
     */
    private function cleanEmployeeData(array $employees): array
    {
        return array_map(function ($employee) {
            $numericFields = [
                'total_earnings',
                'gross_salary',
                'total_deductions',
                'net_salary',
                'paye_tax',
            ];

            foreach ($numericFields as $field) {
                if (isset($employee[$field])) {
                    $employee[$field] = $this->ensureNumeric($employee[$field]);
                }
            }

            // Earnings breakdown
            if (isset($employee['earnings_breakdown']) && is_array($employee['earnings_breakdown'])) {
                foreach ($employee['earnings_breakdown'] as $key => $value) {
                    $employee['earnings_breakdown'][$key] = $this->ensureNumeric($value);
                }
            }

            // Deductions breakdown
            if (isset($employee['deductions_breakdown']) && is_array($employee['deductions_breakdown'])) {
                foreach ($employee['deductions_breakdown'] as $key => $value) {
                    $employee['deductions_breakdown'][$key] = $this->ensureNumeric($value);
                }
            }

            return $employee;
        }, $employees);
    }

    /**
     * Clean breakdown data arrays.
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

    // ======================================================================
    // CSV export
    // ======================================================================

    /**
     * Export report to CSV.
     *
     * When the result set spans multiple currencies a "Currency" column is
     * inserted per row so consumers can always tell which monetary values
     * correspond to which currency.
     */
    public function exportToCsv(array $data, string $filename, string $reportType = 'payroll')
    {
        $callback = function () use ($data, $reportType) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            Log::info('EXPORT_SERVICE: Starting CSV generation', [
                'report_type'          => $reportType,
                'data_structure'       => array_keys($data),
                'has_summary'          => isset($data['summary']),
                'is_multi_currency'    => $data['is_multi_currency'] ?? false,
            ]);

            $reportData      = $this->extractReportDataForCsv($data, $reportType);
            $isMultiCurrency = $data['is_multi_currency']
                ?? $data['summary']['is_multi_currency']
                ?? false;

            switch ($reportType) {
                case 'payroll':
                    $this->generateDetailedPayrollCsv($file, $reportData, $isMultiCurrency);
                    break;
                case 'earnings':
                    $this->generateEarningsCsv($file, $reportData, $isMultiCurrency);
                    break;
                case 'deductions':
                    $this->generateDeductionsCsv($file, $reportData, $isMultiCurrency);
                    break;
                default:
                    $this->generateSimpleCsv($file, $reportData['data'] ?? []);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Normalise the data structure for CSV generation regardless of where it
     * came from (controller wrapper vs. service output).
     */
    private function extractReportDataForCsv(array $data, string $reportType): array
    {
        // If data is wrapped in a controller JSON response, unwrap it
        if (isset($data['success']) && isset($data['data']) && is_array($data['data'])) {
            $result = $data['data'];
        } else {
            $result = $data;
        }

        // Ensure payslip_details is populated from data sub-key when available
        if (!isset($result['payslip_details']) && isset($result['data']) && is_array($result['data'])) {
            $result['payslip_details'] = $result['data'];
        }

        return $result;
    }

    // ------------------------------------------------------------------
    // Per-type CSV generators
    // ------------------------------------------------------------------

    /**
     * Detailed payroll CSV — includes all dynamic earning and deduction columns.
     * When $isMultiCurrency is true a "Currency" column is inserted after Country.
     */
    private function generateDetailedPayrollCsv($file, array $data, bool $isMultiCurrency): void
    {
        Log::info('EXPORT_SERVICE: Generating detailed payroll CSV', [
            'payslip_count'        => count($data['payslip_details'] ?? []),
            'has_earning_headers'  => isset($data['earning_headers']),
            'has_deduction_headers' => isset($data['deduction_headers']),
            'is_multi_currency'    => $isMultiCurrency,
        ]);

        $earningHeaders   = $data['earning_headers']   ?? [];
        $deductionHeaders = $data['deduction_headers'] ?? [];

        // Fall back to reading headers from the first payslip row
        if (empty($earningHeaders) && !empty($data['payslip_details'])) {
            $earningHeaders = array_keys($data['payslip_details'][0]['earnings_breakdown'] ?? []);
        }
        if (empty($deductionHeaders) && !empty($data['payslip_details'])) {
            $deductionHeaders = array_keys($data['payslip_details'][0]['deductions_breakdown'] ?? []);
        }

        // Build header row
        $csvHeaders = [
            'Employee Name',
            'Employee ID',
            'Business',
            'Country',
        ];
        if ($isMultiCurrency) {
            $csvHeaders[] = 'Currency';
        }
        $csvHeaders = array_merge($csvHeaders, [
            'Department',
            'Pay Period',
            'Period Start',
            'Period End',
            'Status',
            'Gross Salary',
        ]);

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

        // Data rows
        foreach ($data['payslip_details'] ?? [] as $row) {
            $csvRow = [
                $row['employee_name']   ?? 'N/A',
                $row['employee_id']     ?? '',
                $row['business']        ?? 'N/A',
                $row['country']         ?? 'N/A',
            ];
            if ($isMultiCurrency) {
                // Use the per-row currency (always resolved from employee's country)
                $csvRow[] = $row['currency'] ?? 'N/A';
            }
            $csvRow = array_merge($csvRow, [
                $row['department']      ?? 'N/A',
                $row['pay_period']      ?? 'N/A',
                $row['pay_period_start'] ?? 'N/A',
                $row['pay_period_end']  ?? 'N/A',
                $row['status']          ?? 'N/A',
                $this->ensureNumeric($row['gross_salary'] ?? 0),
            ]);

            $totalEarnings = 0;
            foreach ($earningHeaders as $header) {
                $amount     = $this->ensureNumeric($row['earnings_breakdown'][$header] ?? 0);
                $csvRow[]   = $amount;
                $totalEarnings += $amount;
            }
            $csvRow[] = $this->ensureNumeric($row['total_earnings'] ?? $totalEarnings);

            $totalDeductions = 0;
            foreach ($deductionHeaders as $header) {
                $amount      = $this->ensureNumeric($row['deductions_breakdown'][$header] ?? 0);
                $csvRow[]    = $amount;
                $totalDeductions += $amount;
            }
            $csvRow[] = $this->ensureNumeric($row['total_deductions'] ?? $totalDeductions);
            $csvRow[] = $this->ensureNumeric($row['paye_tax']   ?? 0);
            $csvRow[] = $this->ensureNumeric($row['net_salary'] ?? 0);

            fputcsv($file, $csvRow);
        }

        // Summary footer
        fputcsv($file, []);
        fputcsv($file, ['SUMMARY']);
        fputcsv($file, ['Total Employees Processed:', $data['processed_employees'] ?? count($data['payslip_details'] ?? [])]);

        if ($isMultiCurrency) {
            fputcsv($file, ['Note: Report contains multiple currencies. See Currency column per row.']);
        } else {
            $currency = $data['currency'] ?? '';
            fputcsv($file, ['Currency:', $currency]);
            fputcsv($file, ['Total Gross Salary:',   $this->ensureNumeric($data['total_gross_salary']  ?? 0)]);
            fputcsv($file, ['Total Net Salary:',     $this->ensureNumeric($data['total_net_salary']    ?? 0)]);
            fputcsv($file, ['Total Earnings:',       $this->ensureNumeric($data['total_earnings']      ?? 0)]);
            fputcsv($file, ['Total Deductions:',     $this->ensureNumeric($data['total_all_deductions'] ?? $data['total_deductions'] ?? 0)]);
            fputcsv($file, ['Total PAYE Tax:',       $this->ensureNumeric($data['total_paye_tax']      ?? 0)]);
        }
    }

    /**
     * Earnings CSV.
     */
    private function generateEarningsCsv($file, array $data, bool $isMultiCurrency): void
    {
        Log::info('EXPORT_SERVICE: Generating earnings CSV', [
            'is_multi_currency' => $isMultiCurrency,
        ]);

        $earningHeaders = $data['earning_headers'] ?? [];

        if (empty($earningHeaders) && !empty($data['employee_earnings'])) {
            $earningHeaders = array_keys($data['employee_earnings'][0]['earnings_breakdown'] ?? []);
        }

        $csvHeaders = ['Employee Name', 'Employee ID', 'Business', 'Country'];
        if ($isMultiCurrency) {
            $csvHeaders[] = 'Currency';
        }
        $csvHeaders = array_merge($csvHeaders, ['Department', 'Pay Period', 'Period Start', 'Period End', 'Status']);

        foreach ($earningHeaders as $header) {
            $csvHeaders[] = $header;
        }
        $csvHeaders[] = 'Total Earnings';
        $csvHeaders[] = 'Gross Salary';

        fputcsv($file, $csvHeaders);

        $employees = $data['employee_earnings'] ?? $data['payslip_details'] ?? [];

        foreach ($employees as $row) {
            $csvRow = [
                $row['employee_name']   ?? 'N/A',
                $row['employee_id']     ?? '',
                $row['business']        ?? 'N/A',
                $row['country']         ?? 'N/A',
            ];
            if ($isMultiCurrency) {
                $csvRow[] = $row['currency'] ?? 'N/A';
            }
            $csvRow = array_merge($csvRow, [
                $row['department']      ?? 'N/A',
                $row['pay_period']      ?? 'N/A',
                $row['pay_period_start'] ?? 'N/A',
                $row['pay_period_end']  ?? 'N/A',
                $row['status']          ?? 'N/A',
            ]);

            $totalEarnings = 0;
            foreach ($earningHeaders as $header) {
                $amount     = $this->ensureNumeric($row['earnings_breakdown'][$header] ?? 0);
                $csvRow[]   = $amount;
                $totalEarnings += $amount;
            }
            $csvRow[] = $this->ensureNumeric($row['total_earnings'] ?? $totalEarnings);
            $csvRow[] = $this->ensureNumeric($row['gross_salary']   ?? 0);

            fputcsv($file, $csvRow);
        }
    }

    /**
     * Deductions CSV.
     */
    private function generateDeductionsCsv($file, array $data, bool $isMultiCurrency): void
    {
        Log::info('EXPORT_SERVICE: Generating deductions CSV', [
            'is_multi_currency' => $isMultiCurrency,
        ]);

        $deductionHeaders = $data['deduction_headers'] ?? [];

        if (empty($deductionHeaders) && !empty($data['employee_deductions'])) {
            $deductionHeaders = array_keys($data['employee_deductions'][0]['deductions_breakdown'] ?? []);
        }

        $csvHeaders = ['Employee Name', 'Employee ID', 'Business', 'Country'];
        if ($isMultiCurrency) {
            $csvHeaders[] = 'Currency';
        }
        $csvHeaders = array_merge($csvHeaders, ['Department', 'Pay Period', 'Period Start', 'Period End', 'Status']);

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
                $row['employee_id']   ?? '',
                $row['business']      ?? 'N/A',
                $row['country']       ?? 'N/A',
            ];
            if ($isMultiCurrency) {
                $csvRow[] = $row['currency'] ?? 'N/A';
            }
            $csvRow = array_merge($csvRow, [
                $row['department']      ?? 'N/A',
                $row['pay_period']      ?? 'N/A',
                $row['pay_period_start'] ?? 'N/A',
                $row['pay_period_end']  ?? 'N/A',
                $row['status']          ?? 'N/A',
            ]);

            $totalDeductions = 0;
            foreach ($deductionHeaders as $header) {
                $amount      = $this->ensureNumeric($row['deductions_breakdown'][$header] ?? 0);
                $csvRow[]    = $amount;
                $totalDeductions += $amount;
            }
            $csvRow[] = $this->ensureNumeric($row['total_deductions'] ?? $totalDeductions);
            $csvRow[] = $this->ensureNumeric($row['paye_tax']   ?? 0);
            $csvRow[] = $this->ensureNumeric($row['net_salary'] ?? 0);

            fputcsv($file, $csvRow);
        }
    }

    /**
     * Simple CSV fallback for flat data arrays (attendance / leave).
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