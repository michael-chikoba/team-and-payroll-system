<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #333;
            margin: 0;
            padding: 20px;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            color: #1f2937;
            font-size: 24px;
        }

        .summary {
            background: #f0f9ff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #3b82f6;
        }

        .summary h2 {
            color: #1e40af;
            margin-top: 0;
        }

        /* Multi-currency notice banner */
        .multi-currency-notice {
            background: #fffbeb;
            border: 1px solid #f59e0b;
            border-left: 4px solid #f59e0b;
            border-radius: 6px;
            padding: 10px 15px;
            margin-bottom: 20px;
            font-size: 9px;
            color: #92400e;
        }

        .multi-currency-notice strong {
            display: block;
            margin-bottom: 3px;
            font-size: 10px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .summary-item {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #dbeafe;
        }

        .summary-item label {
            display: block;
            font-size: 9px;
            color: #6b7280;
            margin-bottom: 5px;
        }

        .summary-item value {
            display: block;
            font-size: 14px;
            font-weight: bold;
            color: #1f2937;
        }

        .summary-item .multi-currency-tag {
            font-size: 8px;
            font-weight: normal;
            color: #f59e0b;
            display: block;
            margin-top: 2px;
        }

        .breakdown-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .breakdown-title {
            font-size: 14px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e5e7eb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 9px;
        }

        th {
            background: #f8fafc;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
            font-size: 8px;
            padding: 8px 4px;
            text-align: left;
            border-bottom: 2px solid #e5e7eb;
        }

        td {
            padding: 6px 4px;
            border-bottom: 1px solid #f3f4f6;
        }

        tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .currency {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }

        /* Currency badge shown per row in multi-currency reports */
        .currency-badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 8px;
            font-size: 8px;
            font-weight: bold;
            background: #e0e7ff;
            color: #4f46e5;
            margin-left: 3px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .type-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
        }

        .type-basic      { background: #dbeafe; color: #1e40af; }
        .type-allowance  { background: #d1fae5; color: #065f46; }
        .type-overtime   { background: #fef3c7; color: #92400e; }
        .type-bonus      { background: #fce7f3; color: #9f1239; }
        .type-tax        { background: #fee2e2; color: #991b1b; }
        .type-statutory  { background: #fef3c7; color: #92400e; }
        .type-pension    { background: #e0e7ff; color: #4f46e5; }
        .type-health     { background: #d1fae5; color: #065f46; }
        .type-voluntary  { background: #fce7f3; color: #9f1239; }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 8px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    @php
        /*
         * ----------------------------------------------------------------
         * Currency resolution
         *
         * $isMultiCurrency  — true when the result set spans >1 currency.
         * $reportCurrency   — the single shared currency code (or null).
         * $currencySymbol   — the single shared symbol (or null).
         *
         * When $isMultiCurrency is true we show per-row currency badges
         * and suppress report-level monetary totals (they would be
         * meaningless across different currencies).
         * ----------------------------------------------------------------
         */
        $isMultiCurrency  = $report['is_multi_currency'] ?? false;
        $reportCurrency   = $report['currency']          ?? $report['filters']['currency']        ?? null;
        $currencySymbol   = $report['currency_symbol']   ?? $report['filters']['currency_symbol'] ?? null;

        // Header meta
        $countryName  = $report['filters']['country'] ?? $report['country'] ?? null;
        $periodStart  = isset($report['period_start']) ? \Carbon\Carbon::parse($report['period_start']) : null;
        $periodEnd    = isset($report['period_end'])   ? \Carbon\Carbon::parse($report['period_end'])   : null;

        /*
         * Helper: format a monetary amount for display.
         *
         * In a multi-currency report the per-row currency is shown as a
         * badge next to the amount; the symbol is omitted at report level
         * because it could belong to any of several currencies.
         *
         * @param  float       $amount
         * @param  string|null $rowCurrency      Per-row currency code (multi-currency only)
         * @param  string|null $rowSymbol        Per-row currency symbol (multi-currency only)
         * @return string
         */
        $formatAmount = function (float $amount, ?string $rowCurrency = null, ?string $rowSymbol = null) use ($isMultiCurrency, $currencySymbol) {
            $formatted = number_format($amount, 2);

            if ($isMultiCurrency) {
                // Show the per-row currency as a small badge; no shared symbol at report level
                $badge = $rowCurrency ? '<span class="currency-badge">' . htmlspecialchars($rowCurrency) . '</span>' : '';
                return $formatted . $badge;
            }

            // Single-currency: use the shared symbol (may still be null)
            $sym = $currencySymbol ? htmlspecialchars($currencySymbol) . ' ' : '';
            return $sym . $formatted;
        };
    @endphp

    {{-- ================================================================ --}}
    {{-- Report header                                                      --}}
    {{-- ================================================================ --}}
    <div class="header">
        <h1>
            Payroll Report
            @if($countryName) — {{ $countryName }} @endif
            @if($isMultiCurrency) — <span style="font-size:16px;color:#f59e0b;">Multi-Currency</span> @endif
        </h1>
        <p><strong>Generated:</strong> {{ now()->format('F d, Y - H:i') }}</p>

        @if($periodStart && $periodEnd)
            <p><strong>Period:</strong> {{ $periodStart->format('F d, Y') }} to {{ $periodEnd->format('F d, Y') }}</p>
        @endif

        @if(isset($report['department']) && $report['department'] !== 'All Departments')
            <p><strong>Department:</strong> {{ $report['department'] }}</p>
        @endif

        @if(isset($report['filters']['business']))
            <p><strong>Business:</strong> {{ $report['filters']['business'] }}</p>
        @endif

        @if($countryName)
            <p><strong>Country:</strong> {{ $countryName }}</p>
        @endif

        @if(!$isMultiCurrency && $reportCurrency)
            <p><strong>Currency:</strong> {{ $reportCurrency }}</p>
        @elseif($isMultiCurrency)
            <p><strong>Currency:</strong> Multiple currencies — see per-row Currency column</p>
        @endif
    </div>

    {{-- ================================================================ --}}
    {{-- Multi-currency notice banner                                        --}}
    {{-- ================================================================ --}}
    @if($isMultiCurrency)
    <div class="multi-currency-notice">
        <strong>⚠ Multi-Currency Report</strong>
        This report includes employees from multiple countries with different currencies.
        Monetary values in the summary totals below are <strong>not cross-currency aggregates</strong>;
        they are provided per-country in the breakdown tables.
        The per-row Currency column identifies the currency for each employee's figures.
    </div>
    @endif

    {{-- ================================================================ --}}
    {{-- Summary overview                                                    --}}
    {{-- ================================================================ --}}
    @if(isset($report['processed_employees']) && $report['processed_employees'] > 0)
    <div class="summary">
        <h2>Summary Overview</h2>
        <div class="summary-grid">

            <div class="summary-item">
                <label>Total Employees</label>
                <value>{{ $report['processed_employees'] }}</value>
            </div>

            @if(isset($report['total_gross_salary']))
            <div class="summary-item">
                <label>Total Gross Salary</label>
                @if($isMultiCurrency)
                    <value class="currency">See breakdown below</value>
                    <span class="multi-currency-tag">Amounts are in multiple currencies</span>
                @else
                    <value class="currency">{!! $formatAmount((float)$report['total_gross_salary']) !!}</value>
                @endif
            </div>
            @endif

            @if(isset($report['total_earnings']))
            <div class="summary-item">
                <label>Total Earnings</label>
                @if($isMultiCurrency)
                    <value class="currency">See breakdown below</value>
                    <span class="multi-currency-tag">Amounts are in multiple currencies</span>
                @else
                    <value class="currency">{!! $formatAmount((float)$report['total_earnings']) !!}</value>
                @endif
            </div>
            @endif

            @if(isset($report['total_all_deductions']) || isset($report['total_deductions']))
            <div class="summary-item">
                <label>Total Deductions</label>
                @if($isMultiCurrency)
                    <value class="currency">See breakdown below</value>
                    <span class="multi-currency-tag">Amounts are in multiple currencies</span>
                @else
                    <value class="currency">{!! $formatAmount((float)($report['total_all_deductions'] ?? $report['total_deductions'])) !!}</value>
                @endif
            </div>
            @endif

            @if(isset($report['total_paye_tax']))
            <div class="summary-item">
                <label>Total PAYE Tax</label>
                @if($isMultiCurrency)
                    <value class="currency">See breakdown below</value>
                    <span class="multi-currency-tag">Amounts are in multiple currencies</span>
                @else
                    <value class="currency">{!! $formatAmount((float)$report['total_paye_tax']) !!}</value>
                @endif
            </div>
            @endif

            @if(isset($report['total_net_salary']))
            <div class="summary-item">
                <label>Total Net Salary</label>
                @if($isMultiCurrency)
                    <value class="currency">See breakdown below</value>
                    <span class="multi-currency-tag">Amounts are in multiple currencies</span>
                @else
                    <value class="currency">{!! $formatAmount((float)$report['total_net_salary']) !!}</value>
                @endif
            </div>
            @endif

        </div>
    </div>
    @endif

    {{-- ================================================================ --}}
    {{-- Employee payroll details table                                      --}}
    {{-- ================================================================ --}}
    @if(isset($report['payslip_details']) && count($report['payslip_details']) > 0)
    <div class="breakdown-section">
        <div class="breakdown-title">Employee Payroll Details</div>
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    @if($isMultiCurrency)
                        <th>Country</th>
                        <th>Currency</th>
                    @endif
                    <th class="text-right">Gross</th>
                    <th class="text-right">Deductions</th>
                    <th class="text-right">Net Pay</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['payslip_details'] as $payslip)
                @php
                    $rowCurrency = $payslip['currency']        ?? null;
                    $rowSymbol   = $payslip['currency_symbol'] ?? null;
                @endphp
                <tr>
                    <td>{{ $payslip['employee_name'] ?? 'N/A' }}</td>
                    <td>{{ $payslip['department']    ?? 'N/A' }}</td>
                    @if($isMultiCurrency)
                        <td>{{ $payslip['country'] ?? 'N/A' }}</td>
                        <td class="text-center">
                            @if($rowCurrency)
                                <span class="currency-badge">{{ $rowCurrency }}</span>
                            @else
                                —
                            @endif
                        </td>
                    @endif
                    <td class="text-right currency">
                        @if($isMultiCurrency)
                            {{ $rowSymbol ? $rowSymbol . ' ' : '' }}{{ number_format((float)($payslip['gross_salary'] ?? 0), 2) }}
                        @else
                            {!! $formatAmount((float)($payslip['gross_salary'] ?? 0)) !!}
                        @endif
                    </td>
                    <td class="text-right currency">
                        @if($isMultiCurrency)
                            {{ $rowSymbol ? $rowSymbol . ' ' : '' }}{{ number_format((float)($payslip['total_deductions'] ?? 0), 2) }}
                        @else
                            {!! $formatAmount((float)($payslip['total_deductions'] ?? 0)) !!}
                        @endif
                    </td>
                    <td class="text-right currency">
                        @if($isMultiCurrency)
                            {{ $rowSymbol ? $rowSymbol . ' ' : '' }}{{ number_format((float)($payslip['net_salary'] ?? 0), 2) }}
                        @else
                            {!! $formatAmount((float)($payslip['net_salary'] ?? 0)) !!}
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ================================================================ --}}
    {{-- Department summary                                                  --}}
    {{-- In multi-currency mode we show totals per department but note that  --}}
    {{-- cross-department aggregation is not meaningful.                     --}}
    {{-- ================================================================ --}}
    @if(isset($report['department_breakdown']) && count($report['department_breakdown']) > 0)
    <div class="breakdown-section">
        <div class="breakdown-title">Department Summary</div>

        @if($isMultiCurrency)
        <p style="font-size:9px;color:#92400e;margin:0 0 8px 0;">
            ⚠ Totals below may span multiple currencies and should not be summed across departments without checking the employee-level detail above.
        </p>
        @endif

        <table>
            <thead>
                <tr>
                    <th>Department</th>
                    <th class="text-right">Employees</th>
                    <th class="text-right">Total Gross</th>
                    <th class="text-right">Total Net</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['department_breakdown'] as $dept => $deptData)
                <tr>
                    <td>{{ $dept }}</td>
                    <td class="text-right">{{ $deptData['employee_count'] ?? 0 }}</td>
                    <td class="text-right currency">
                        @if($isMultiCurrency)
                            {{ number_format((float)($deptData['total_gross_salary'] ?? 0), 2) }}
                            <span class="multi-currency-tag" style="display:inline;margin-left:3px;">*</span>
                        @else
                            {!! $formatAmount((float)($deptData['total_gross_salary'] ?? 0)) !!}
                        @endif
                    </td>
                    <td class="text-right currency">
                        @if($isMultiCurrency)
                            {{ number_format((float)($deptData['total_net_salary'] ?? 0), 2) }}
                            <span class="multi-currency-tag" style="display:inline;margin-left:3px;">*</span>
                        @else
                            {!! $formatAmount((float)($deptData['total_net_salary'] ?? 0)) !!}
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($isMultiCurrency)
        <p style="font-size:8px;color:#6b7280;margin:4px 0 0 0;">
            * Amounts marked with * are raw numeric totals and may represent different currencies.
        </p>
        @endif
    </div>
    @endif

    {{-- ================================================================ --}}
    {{-- Footer                                                              --}}
    {{-- ================================================================ --}}
    <div class="footer">
        <p>Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
        @if(isset($report['processed_employees']))
        <p>Report includes {{ $report['processed_employees'] }} employees</p>
        @endif
        @if($isMultiCurrency)
        <p>This report spans multiple currencies. Refer to the Currency column for per-employee denomination.</p>
        @elseif($reportCurrency)
        <p>All monetary values are in {{ $reportCurrency }}{{ $currencySymbol ? ' (' . $currencySymbol . ')' : '' }}</p>
        @endif
    </div>
</body>
</html>