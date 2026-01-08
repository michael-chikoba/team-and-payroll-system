<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deductions Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
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
            background: #fef2f2;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #ef4444;
        }
        
        .summary h2 {
            color: #b91c1c;
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
            border: 1px solid #fee2e2;
        }
        
        .type-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .type-tax { background: #fef3c7; color: #92400e; }
        .type-statutory { background: #d1fae5; color: #065f46; }
        .type-pension { background: #dbeafe; color: #1e40af; }
        .type-health { background: #d1fae5; color: #065f46; }
        .type-voluntary { background: #fce7f3; color: #9f1239; }
        .type-loan { background: #e0e7ff; color: #4f46e5; }
        .type-other { background: #f3f4f6; color: #4b5563; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8fafc;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
            font-size: 10px;
        }
        
        .currency {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    @php
        $currency = $report['currency'] ?? ($report['filters']['currency'] ?? 'KES');
        $currencySymbol = $report['currency_symbol'] ?? ($report['filters']['currency_symbol'] ?? 'KES');
    @endphp

    <div class="header">
        <h1>📉 Deductions Report</h1>
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
        <h2>Deductions Summary</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <label>Total Deductions</label>
                <div class="currency">{{ $currencySymbol }} {{ number_format($report['total_deductions'] ?? 0, 2) }}</div>
            </div>
            <div class="summary-item">
                <label>Total PAYE Tax</label>
                <div class="currency">{{ $currencySymbol }} {{ number_format($report['total_paye_tax'] ?? 0, 2) }}</div>
            </div>
            <div class="summary-item">
                <label>Employees Processed</label>
                <div>{{ $report['processed_employees'] ?? 0 }}</div>
            </div>
            <div class="summary-item">
                <label>Average Deductions</label>
                <div class="currency">{{ $currencySymbol }} {{ number_format($report['average_deductions'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Deductions Breakdown -->
    @if(isset($report['deduction_breakdown']) && count($report['deduction_breakdown']) > 0)
    <div style="margin-top: 30px;">
        <h3>Deductions Breakdown by Type</h3>
        <table>
            <thead>
                <tr>
                    <th>Deduction Type</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th class="text-right">Total Amount</th>
                    <th class="text-right">Employees</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['deduction_breakdown'] as $deduction)
                <tr>
                    <td>{{ $deduction['name'] ?? 'N/A' }}</td>
                    <td>{{ $deduction['description'] ?? 'N/A' }}</td>
                    <td><span class="type-badge type-{{ $deduction['type'] ?? 'other' }}">{{ $deduction['type'] ?? 'other' }}</span></td>
                    <td class="text-right currency">{{ $currencySymbol }} {{ number_format($deduction['total_amount'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ $deduction['employee_count'] ?? 0 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Employee Deductions Details -->
    @if(isset($report['employee_deductions']) && count($report['employee_deductions']) > 0)
    <div style="margin-top: 30px; page-break-before: always;">
        <h3>Employee Deductions Details</h3>
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Pay Period</th>
                    @if(isset($report['deduction_headers']))
                        @foreach($report['deduction_headers'] as $header)
                        <th class="text-right">{{ $header }}</th>
                        @endforeach
                    @endif
                    <th class="text-right">Total Deductions</th>
                    <th class="text-right">PAYE Tax</th>
                    <th class="text-right">Net Salary</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['employee_deductions'] as $employee)
                <tr>
                    <td>{{ $employee['employee_name'] ?? 'N/A' }}</td>
                    <td>{{ $employee['department'] ?? 'N/A' }}</td>
                    <td>{{ $employee['pay_period'] ?? 'N/A' }}</td>
                    @if(isset($report['deduction_headers']))
                        @foreach($report['deduction_headers'] as $header)
                        <td class="text-right currency">
                            {{ $currencySymbol }} {{ number_format($employee['deductions_breakdown'][$header] ?? 0, 2) }}
                        </td>
                        @endforeach
                    @endif
                    <td class="text-right currency">{{ $currencySymbol }} {{ number_format($employee['total_deductions'] ?? 0, 2) }}</td>
                    <td class="text-right currency">{{ $currencySymbol }} {{ number_format($employee['paye_tax'] ?? 0, 2) }}</td>
                    <td class="text-right currency">{{ $currencySymbol }} {{ number_format($employee['net_salary'] ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div style="margin-top: 40px; text-align: center; font-size: 10px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 20px;">
        <p>This is a computer-generated report. Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
    </div>
</body>
</html>