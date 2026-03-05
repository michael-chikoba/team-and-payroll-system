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
        
        .text-right {
            text-align: right;
        }
        
        .type-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
        }
        
        .type-basic { background: #dbeafe; color: #1e40af; }
        .type-allowance { background: #d1fae5; color: #065f46; }
        .type-overtime { background: #fef3c7; color: #92400e; }
        .type-bonus { background: #fce7f3; color: #9f1239; }
        .type-tax { background: #fee2e2; color: #991b1b; }
        .type-statutory { background: #fef3c7; color: #92400e; }
        .type-pension { background: #e0e7ff; color: #4f46e5; }
        .type-health { background: #d1fae5; color: #065f46; }
        .type-voluntary { background: #fce7f3; color: #9f1239; }
        
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
        $countryName = $report['filters']['country'] ?? $report['country'] ?? null;
        $currencyCode = $report['currency'] ?? $report['filters']['currency'] ?? null;
        $currencySymbol = $report['currency_symbol'] ?? $report['filters']['currency_symbol'] ?? $currencyCode;
        $periodStart = isset($report['period_start']) ? \Carbon\Carbon::parse($report['period_start']) : null;
        $periodEnd = isset($report['period_end']) ? \Carbon\Carbon::parse($report['period_end']) : null;
    @endphp

    <div class="header">
        <h1>Payroll Report @if($countryName)- {{ $countryName }}@endif</h1>
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
        
        @if($currencyCode)
            <p><strong>Currency:</strong> {{ $currencyCode }}</p>
        @endif
    </div>

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
                <value class="currency">{{ $currencySymbol ?? '' }} {{ number_format($report['total_gross_salary'], 2) }}</value>
            </div>
            @endif
            
            @if(isset($report['total_earnings']))
            <div class="summary-item">
                <label>Total Earnings</label>
                <value class="currency">{{ $currencySymbol ?? '' }} {{ number_format($report['total_earnings'], 2) }}</value>
            </div>
            @endif
            
            @if(isset($report['total_all_deductions']) || isset($report['total_deductions']))
            <div class="summary-item">
                <label>Total Deductions</label>
                <value class="currency">{{ $currencySymbol ?? '' }} {{ number_format($report['total_all_deductions'] ?? $report['total_deductions'], 2) }}</value>
            </div>
            @endif
            
            @if(isset($report['total_paye_tax']))
            <div class="summary-item">
                <label>Total PAYE Tax</label>
                <value class="currency">{{ $currencySymbol ?? '' }} {{ number_format($report['total_paye_tax'], 2) }}</value>
            </div>
            @endif
            
            @if(isset($report['total_net_salary']))
            <div class="summary-item">
                <label>Total Net Salary</label>
                <value class="currency">{{ $currencySymbol ?? '' }} {{ number_format($report['total_net_salary'], 2) }}</value>
            </div>
            @endif
        </div>
    </div>
    @endif

    @if(isset($report['payslip_details']) && count($report['payslip_details']) > 0)
    <div class="breakdown-section">
        <div class="breakdown-title">Employee Payroll Details</div>
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    <th class="text-right">Gross</th>
                    <th class="text-right">Deductions</th>
                    <th class="text-right">Net Pay</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['payslip_details'] as $payslip)
                <tr>
                    <td>{{ $payslip['employee_name'] ?? 'N/A' }}</td>
                    <td>{{ $payslip['department'] ?? 'N/A' }}</td>
                    <td class="text-right currency">{{ $currencySymbol ?? '' }} {{ number_format($payslip['gross_salary'] ?? 0, 2) }}</td>
                    <td class="text-right currency">{{ $currencySymbol ?? '' }} {{ number_format($payslip['total_deductions'] ?? 0, 2) }}</td>
                    <td class="text-right currency">{{ $currencySymbol ?? '' }} {{ number_format($payslip['net_salary'] ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(isset($report['department_breakdown']) && count($report['department_breakdown']) > 0)
    <div class="breakdown-section">
        <div class="breakdown-title">Department Summary</div>
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
                @foreach($report['department_breakdown'] as $dept => $data)
                <tr>
                    <td>{{ $dept }}</td>
                    <td class="text-right">{{ $data['employee_count'] ?? 0 }}</td>
                    <td class="text-right currency">{{ $currencySymbol ?? '' }} {{ number_format($data['total_gross_salary'] ?? 0, 2) }}</td>
                    <td class="text-right currency">{{ $currencySymbol ?? '' }} {{ number_format($data['total_net_salary'] ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
        @if(isset($report['processed_employees']))
        <p>Report includes {{ $report['processed_employees'] }} employees</p>
        @endif
    </div>
</body>
</html>