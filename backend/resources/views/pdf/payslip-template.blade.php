<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip - {{ $employee->user->first_name ?? '' }} {{ $employee->user->last_name ?? '' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            margin: 0;
            padding: 20px;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .company-name {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
        }
        .payslip-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 15px;
        }
      
        /* Proper table styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
      
        table.info-table {
            border: 1px solid #000;
        }
      
        table.info-table td {
            padding: 8px 10px;
            border: 1px solid #000;
            vertical-align: top;
        }
      
        table.info-table td.label {
            font-weight: bold;
            width: 15%;
            background-color: #f5f5f5;
        }
      
        table.info-table td.value {
            width: 35%;
        }
      
        /* Earnings & Deductions Table */
        table.earnings-deductions {
            border: 1px solid #000;
            margin-top: 20px;
        }
      
        table.earnings-deductions th {
            background-color: #e0e0e0;
            font-weight: bold;
            padding: 10px;
            border: 1px solid #000;
            text-align: left;
        }
      
        table.earnings-deductions td {
            padding: 8px 10px;
            border: 1px solid #000;
        }
      
        table.earnings-deductions td.amount {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
      
        table.earnings-deductions tr.total-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }
      
        /* Summary Tables */
        table.summary-table {
            border: 1px solid #000;
            margin-top: 10px;
        }
      
        table.summary-table th {
            background-color: #e0e0e0;
            font-weight: bold;
            padding: 8px;
            border: 1px solid #000;
            text-align: center;
        }
      
        table.summary-table td {
            padding: 8px;
            border: 1px solid #000;
            text-align: center;
        }
      
        .net-pay {
            text-align: right;
            font-weight: bold;
            font-size: 14px;
            margin-top: 15px;
            padding: 15px;
            border: 2px solid #000;
            background-color: #f5f5f5;
        }
      
        .section-title {
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 13px;
            text-decoration: underline;
        }
      
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            padding-top: 20px;
            border-top: 1px solid #ccc;
        }
      
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Company & Payslip Header -->
        <div class="header">
            <div class="company-name">Castle Holdings Ltd</div>
            <div class="payslip-title">Payslip for the month of {{ $payroll->pay_period ?? 'N/A' }}</div>
        </div>
      
        <!-- Employee Details Section -->
        <table class="info-table">
            <tr>
                <td class="label">Code</td>
                <td class="value">{{ $employee->employee_id ?? 'N/A' }}</td>
                <td class="label">Employer</td>
                <td class="value">Castle Holdings Ltd</td>
            </tr>
            <tr>
                <td class="label">Name</td>
                <td class="value">{{ $employee->user->first_name ?? '' }} {{ $employee->user->last_name ?? '' }}</td>
                <td class="label">Period</td>
                <td class="value">{{ $payroll->payroll_period ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Department</td>
                <td class="value">{{ $employee->department ?? 'N/A' }}</td>
                <td class="label">Position</td>
                <td class="value">{{ $employee->position ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Email</td>
                <td class="value">{{ $employee->user->email ?? 'N/A' }}</td>
                <td class="label">Pay Date</td>
                <td class="value">{{ $payslip->payment_date ? \Carbon\Carbon::parse($payslip->payment_date)->format('d/m/Y') : 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Pay Period Start</td>
                <td class="value">{{ $payslip->pay_period_start ? \Carbon\Carbon::parse($payslip->pay_period_start)->format('d/m/Y') : 'N/A' }}</td>
                <td class="label">Pay Period End</td>
                <td class="value">{{ $payslip->pay_period_end ? \Carbon\Carbon::parse($payslip->pay_period_end)->format('d/m/Y') : 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Working Days</td>
                <td class="value">{{ $payroll->working_days ?? '22' }}</td>
                <td class="label">Base Salary</td>
                <td class="value">K{{ number_format($employee->base_salary ?? 0, 2) }}</td>
            </tr>
        </table>
      
        <!-- Earnings & Deductions Table -->
        <table class="earnings-deductions">
            <thead>
                <tr>
                    <th>EARNINGS</th>
                    <th style="text-align: right;">AMOUNT (K)</th>
                    <th>DEDUCTIONS</th>
                    <th style="text-align: right;">AMOUNT (K)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Basic Salary</td>
                    <td class="amount">{{ number_format($payslip->basic_salary ?? 0, 2) }}</td>
                    <td>PAYE Tax</td>
                    <td class="amount">{{ number_format($payslip->paye ?? 0, 2) }}</td>
                </tr>
              
                <tr>
                    <td>Housing Allowance </td>
                    <td class="amount">{{ number_format($payslip->house_allowance ?? 0, 2) }}</td>
                    <td>NAPSA </td>
                    <td class="amount">{{ number_format($payslip->napsa ?? 0, 2) }}</td>
                </tr>
              
                <tr>
                    <td>Transport Allowance</td>
                    <td class="amount">{{ number_format($payslip->transport_allowance ?? 0, 2) }}</td>
                    <td>NHIMA</td>
                    <td class="amount">{{ number_format($payslip->nhima ?? 0, 2) }}</td>
                </tr>
              
                <tr>
                    <td>Lunch Allowance</td>
                    <td class="amount">{{ number_format($payslip->other_allowances ?? 0, 2) }}</td>
                    <td>Other Deductions</td>
                    <td class="amount">{{ number_format($payslip->other_deductions ?? 0, 2) }}</td>
                </tr>
              
                @if($payslip->overtime_hours > 0)
                <tr>
                    <td>Overtime Pay ({{ $payslip->overtime_hours }} hrs @ K{{ number_format($payslip->overtime_rate ?? 0, 2) }})</td>
                    <td class="amount">{{ number_format($payslip->overtime_pay ?? 0, 2) }}</td>
                    <td></td>
                    <td class="amount"></td>
                </tr>
                @endif
              
                <tr class="total-row">
                    <td>GROSS SALARY</td>
                    <td class="amount">{{ number_format($payslip->gross_salary ?? 0, 2) }}</td>
                    <td>TOTAL DEDUCTIONS</td>
                    <td class="amount">{{ number_format($payslip->total_deductions ?? 0, 2) }}</td>
                </tr>
            </tbody>
        </table>
      
        <!-- Net Pay -->
        <div class="net-pay">
            NET PAY: K{{ number_format($payslip->net_pay ?? 0, 2) }}
        </div>
      
        <!-- Footer -->
        <div class="footer">
            <p><strong>Generated on:</strong> {{ now()->format('d/m/Y H:i:s') }}</p>
            <p>This payslip is computer generated and does not require a signature.</p>
            <p>Confidential - For Employee Use Only</p>
            <p><em>Castle Holdings Ltd - 54 Seble Road, Lusaka, Zambia</em></p>
        </div>
    </div>
</body>
</html>