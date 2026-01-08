<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Earnings Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 5px 0;
            font-size: 18px;
            color: #2563eb;
        }
        .header p {
            margin: 3px 0;
            font-size: 10px;
        }
        .summary-section {
            margin: 15px 0;
            padding: 10px;
            background: #f3f4f6;
            border-radius: 5px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            padding: 5px 0;
        }
        .summary-label {
            font-weight: bold;
            color: #4b5563;
        }
        .summary-value {
            color: #1f2937;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 9px;
        }
        th {
            background-color: #3b82f6;
            color: white;
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
        }
        td {
            padding: 6px 5px;
            border-bottom: 1px solid #e5e7eb;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .breakdown-section {
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .breakdown-title {
            font-size: 12px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #d1d5db;
        }
        .breakdown-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        .breakdown-card {
            padding: 8px;
            background: #eff6ff;
            border-left: 3px solid #3b82f6;
            border-radius: 3px;
        }
        .breakdown-card-title {
            font-weight: bold;
            font-size: 9px;
            color: #1e40af;
            margin-bottom: 3px;
        }
        .breakdown-card-amount {
            font-size: 11px;
            font-weight: bold;
            color: #1f2937;
        }
        .breakdown-card-count {
            font-size: 8px;
            color: #6b7280;
            margin-top: 2px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8px;
            color: #6b7280;
            border-top: 1px solid #d1d5db;
            padding-top: 10px;
        }
        .currency {
            font-family: 'DejaVu Sans', sans-serif;
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
        <h1>📈 Earnings Report</h1>
        <p><strong>Period:</strong> {{ \Carbon\Carbon::parse($report['period_start'] ?? now())->format('F d, Y') }} to {{ \Carbon\Carbon::parse($report['period_end'] ?? now())->format('F d, Y') }}</p>
        <p><strong>Department:</strong> {{ $report['department'] ?? 'All Departments' }}</p>
        @if(!empty($report['filters']))
            @if(!empty($report['filters']['business']))
                <p><strong>Business:</strong> {{ $report['filters']['business'] }}</p>
            @endif
            @if(!empty($report['filters']['country']))
                <p><strong>Country:</strong> {{ $report['filters']['country'] }}</p>
            @endif
        @endif
        <p><strong>Currency:</strong> {{ $currency }}</p>
        <p><strong>Generated:</strong> {{ now()->format('F d, Y - H:i') }}</p>
    </div>

    <div class="summary-section">
        <div class="summary-row">
            <span class="summary-label">Total Earnings:</span>
            <span class="summary-value currency">{{ $currencySymbol }} {{ number_format($report['total_earnings'] ?? 0, 2) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Gross Salary:</span>
            <span class="summary-value currency">{{ $currencySymbol }} {{ number_format($report['total_gross_salary'] ?? 0, 2) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Employees Processed:</span>
            <span class="summary-value">{{ $report['processed_employees'] ?? 0 }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Average Earnings:</span>
            <span class="summary-value currency">{{ $currencySymbol }} {{ number_format($report['average_earnings'] ?? 0, 2) }}</span>
        </div>
    </div>

    @if(!empty($report['earning_breakdown']) && count($report['earning_breakdown']) > 0)
    <div class="breakdown-section">
        <div class="breakdown-title">Earnings Breakdown by Type</div>
        <div class="breakdown-grid">
            @foreach($report['earning_breakdown'] as $earning)
            <div class="breakdown-card">
                <div class="breakdown-card-title">{{ $earning['name'] ?? 'Unknown' }}</div>
                <div class="breakdown-card-amount currency">{{ $currencySymbol }} {{ number_format($earning['total_amount'] ?? 0, 2) }}</div>
                <div class="breakdown-card-count">{{ $earning['employee_count'] ?? 0 }} employees</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if(!empty($report['employee_earnings']) && count($report['employee_earnings']) > 0)
    <div style="margin-top: 20px;">
        <h3 style="font-size: 12px; margin-bottom: 10px;">Employee Earnings Details</h3>
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    @foreach($report['earning_headers'] ?? [] as $header)
                    <th class="text-right">{{ $header }}</th>
                    @endforeach
                    <th class="text-right">Total Earnings</th>
                    <th class="text-right">Gross Salary</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['employee_earnings'] as $employee)
                <tr>
                    <td>{{ $employee['employee_name'] ?? 'N/A' }}</td>
                    <td>{{ $employee['department'] ?? 'N/A' }}</td>
                    @foreach($report['earning_headers'] ?? [] as $header)
                    <td class="text-right currency">{{ $currencySymbol }} {{ number_format($employee['earnings_breakdown'][$header] ?? 0, 2) }}</td>
                    @endforeach
                    <td class="text-right currency"><strong>{{ $currencySymbol }} {{ number_format($employee['total_earnings'] ?? 0, 2) }}</strong></td>
                    <td class="text-right currency">{{ $currencySymbol }} {{ number_format($employee['gross_salary'] ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>This is a computer-generated report. Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
    </div>
</body>
</html>