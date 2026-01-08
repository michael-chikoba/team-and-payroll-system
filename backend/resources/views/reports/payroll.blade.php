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
        $currency = $report['currency'] ?? ($report['filters']['currency'] ?? 'KES');
        $currencySymbol = $report['currency_symbol'] ?? ($report['filters']['currency_symbol'] ?? 'KES');
    @endphp

    <div class="header">
        <h1>💼 Comprehensive Payroll Report</h1>
        <p><strong>Generated:</strong> {{ now()->format('F d, Y - H:i') }}</p>
        <p><strong>Period:</strong> {{ \Carbon\Carbon::parse($report['period_start'] ?? now())->format('F d, Y') }} to {{ \Carbon\Carbon::parse($report['period_end'] ?? now())->format('F d, Y') }}</p>
        @if(isset($report['department']) && $report['department'] !== 'All Departments')
            <p><strong>Department:</strong> {{ $report['department'] }}</p>
        @endif
        @if(isset($report['filters']['business']))
            <p><strong>Business:</strong> {{ $report['filters']['business'] }}</p>
        @endif
        @if(isset($report['filters']['country']))
            <p><strong>Country:</strong> {{ $report['filters']['country'] }}</p>
        @endif
        <p><strong>Currency:</strong> {{ $currency }}</p>
    </div>

    <div class="summary">
        <h2>Summary Overview</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <label>Total Employees</label>
                <value>{{ $report['processed_employees'] ?? 0 }}</value>
            </div>
            <div class="summary-item">
                <label>Total Gross Salary</label>
                <value class="currency">{{ $currencySymbol }} {{ number_format($report['total_gross_salary'] ?? 0, 2) }}</value>
            </div>
            <div class="summary-item">
                <label>Total Earnings</label>
                <value class="currency">{{ $currencySymbol }} {{ number_format($report['total_earnings'] ?? 0, 2) }}</value>
            </div>
            <div class="summary-item">
                <label>Total Deductions</label>
                <value class="currency">{{ $currencySymbol }} {{ number_format($report['total_all_deductions'] ?? $report['total_deductions'] ?? 0, 2) }}</value>
            </div>
            <div class="summary-item">
                <label>Total PAYE Tax</label>
                <value class="currency">{{ $currencySymbol }} {{ number_format($report['total_paye_tax'] ?? 0, 2) }}</value>
            </div>
            <div class="summary-item">
                <label>Total Net Salary</label>
                <value class="currency">{{ $currencySymbol }} {{ number_format($report['total_net_salary'] ?? 0, 2) }}</value>
            </div>
        </div>
    </div>

    <!-- Earnings Breakdown -->
    @if(isset($report['earning_breakdown']) && count($report['earning_breakdown']) > 0)
    <div class="breakdown-section">
        <div class="breakdown-title">📈 Earnings Breakdown by Type</div>
        <table>
            <thead>
                <tr>
                    <th>Earning Type</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th class="text-right">Total Amount</th>
                    <th class="text-right">Employees</th>
                    <th class="text-right">Average</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['earning_breakdown'] as $earning)
                <tr>
                    <td><strong>{{ $earning['name'] ?? 'N/A' }}</strong></td>
                    <td>{{ $earning['description'] ?? 'N/A' }}</td>
                    <td><span class="type-badge type-{{ $earning['type'] ?? 'basic' }}">{{ $earning['type'] ?? 'basic' }}</span></td>
                    <td class="text-right currency">{{ $currencySymbol }} {{ number_format($earning['total_amount'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ $earning['employee_count'] ?? 0 }}</td>
                    <td class="text-right currency">
                        {{ $currencySymbol }} {{ number_format(($earning['employee_count'] ?? 0) > 0 ? ($earning['total_amount'] ?? 0) / $earning['employee_count'] : 0, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Deductions Breakdown -->
    @if(isset($report['deduction_breakdown']) && count($report['deduction_breakdown']) > 0)
    <div class="breakdown-section">
        <div class="breakdown-title">📉 Deductions Breakdown by Type</div>
        <table>
            <thead>
                <tr>
                    <th>Deduction Type</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th class="text-right">Total Amount</th>
                    <th class="text-right">Employees</th>
                    <th class="text-right">Average</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['deduction_breakdown'] as $deduction)
                <tr>
                    <td><strong>{{ $deduction['name'] ?? 'N/A' }}</strong></td>
                    <td>{{ $deduction['description'] ?? 'N/A' }}</td>
                    <td><span class="type-badge type-{{ $deduction['type'] ?? 'statutory' }}">{{ $deduction['type'] ?? 'statutory' }}</span></td>
                    <td class="text-right currency">{{ $currencySymbol }} {{ number_format($deduction['total_amount'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ $deduction['employee_count'] ?? 0 }}</td>
                    <td class="text-right currency">
                        {{ $currencySymbol }} {{ number_format(($deduction['employee_count'] ?? 0) > 0 ? ($deduction['total_amount'] ?? 0) / $deduction['employee_count'] : 0, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Employee Payroll Details with Dynamic Columns -->
    @if(isset($report['payslip_details']) && count($report['payslip_details']) > 0)
    <div class="breakdown-section" style="page-break-before: always;">
        <div class="breakdown-title">👥 Detailed Employee Payroll</div>
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Pay Period</th>
                    <th class="text-right">Gross</th>
                    
                    @if(isset($report['earning_headers']))
                        @foreach($report['earning_headers'] as $header)
                        <th class="text-right">{{ $header }}</th>
                        @endforeach
                    @endif
                    
                    <th class="text-right">Total Earnings</th>
                    
                    @if(isset($report['deduction_headers']))
                        @foreach($report['deduction_headers'] as $header)
                        <th class="text-right">{{ $header }}</th>
                        @endforeach
                    @endif
                    
                    <th class="text-right">Total Deductions</th>
                    <th class="text-right">Net Pay</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['payslip_details'] as $payslip)
                <tr>
                    <td><strong>{{ $payslip['employee_name'] ?? 'N/A' }}</strong></td>
                    <td>{{ $payslip['department'] ?? 'N/A' }}</td>
                    <td>{{ $payslip['pay_period'] ?? 'N/A' }}</td>
                    <td class="text-right currency">{{ $currencySymbol }} {{ number_format($payslip['gross_salary'] ?? 0, 2) }}</td>
                    
                    @if(isset($report['earning_headers']))
                        @foreach($report['earning_headers'] as $header)
                        <td class="text-right currency">
                            {{ $currencySymbol }} {{ number_format($payslip['earnings_breakdown'][$header] ?? 0, 2) }}
                        </td>
                        @endforeach
                    @endif
                    
                    <td class="text-right currency"><strong>{{ $currencySymbol }} {{ number_format($payslip['total_earnings'] ?? 0, 2) }}</strong></td>
                    
                    @if(isset($report['deduction_headers']))
                        @foreach($report['deduction_headers'] as $header)
                        <td class="text-right currency">
                            {{ $currencySymbol }} {{ number_format($payslip['deductions_breakdown'][$header] ?? 0, 2) }}
                        </td>
                        @endforeach
                    @endif
                    
                    <td class="text-right currency"><strong>{{ $currencySymbol }} {{ number_format($payslip['total_deductions'] ?? 0, 2) }}</strong></td>
                    <td class="text-right currency"><strong>{{ $currencySymbol }} {{ number_format($payslip['net_salary'] ?? 0, 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Department Breakdown -->
    @if(isset($report['department_breakdown']) && count($report['department_breakdown']) > 0)
    <div class="breakdown-section" style="page-break-before: always;">
        <div class="breakdown-title">🏢 Department Summary</div>
        <table>
            <thead>
                <tr>
                    <th>Department</th>
                    <th class="text-right">Employees</th>
                    <th class="text-right">Total Gross</th>
                    <th class="text-right">Total Earnings</th>
                    <th class="text-right">Total Deductions</th>
                    <th class="text-right">Total Net</th>
                    <th class="text-right">Avg Net Salary</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['department_breakdown'] as $dept => $data)
                <tr>
                    <td><strong>{{ $dept }}</strong></td>
                    <td class="text-right">{{ $data['employee_count'] ?? 0 }}</td>
                    <td class="text-right currency">{{ $currencySymbol }} {{ number_format($data['total_gross_salary'] ?? 0, 2) }}</td>
                    <td class="text-right currency">{{ $currencySymbol }} {{ number_format($data['total_earnings'] ?? 0, 2) }}</td>
                    <td class="text-right currency">{{ $currencySymbol }} {{ number_format($data['total_deductions'] ?? 0, 2) }}</td>
                    <td class="text-right currency">{{ $currencySymbol }} {{ number_format($data['total_net_salary'] ?? 0, 2) }}</td>
                    <td class="text-right currency">
                        {{ $currencySymbol }} {{ number_format(($data['employee_count'] ?? 0) > 0 ? ($data['total_net_salary'] ?? 0) / $data['employee_count'] : 0, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>This is a computer-generated report. Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
        <p>Report includes {{ $report['processed_employees'] ?? 0 }} employees with {{ count($report['earning_headers'] ?? []) }} earning types and {{ count($report['deduction_headers'] ?? []) }} deduction types</p>
    </div>
</body>
</html>