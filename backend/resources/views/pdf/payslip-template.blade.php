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
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .company-name {
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .company-address {
            font-size: 11px;
            color: #555;
            margin-bottom: 10px;
        }
        .payslip-title {
            font-weight: bold;
            font-size: 16px;
            margin-top: 10px;
        }
      
        /* Info Table */
        table.info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.info-table td {
            padding: 5px;
            vertical-align: top;
        }
        table.info-table td.label {
            font-weight: bold;
            width: 15%;
            color: #333;
        }
        table.info-table td.value {
            width: 35%;
        }
      
        /* Earnings & Deductions Table */
        table.earnings-deductions {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border: 1px solid #000;
        }
        table.earnings-deductions th {
            background-color: #f0f0f0;
            font-weight: bold;
            padding: 8px;
            border: 1px solid #000;
            text-align: left;
            width: 50%;
        }
        table.earnings-deductions td {
            padding: 0;
            border: 1px solid #000;
            vertical-align: top;
        }
        
        /* Inner tables for alignment */
        .inner-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }
        .inner-table td {
            border: none;
            padding: 6px 8px;
            border-bottom: 1px solid #eee;
        }
        .inner-table tr:last-child td {
            border-bottom: none;
        }
        .text-right { text-align: right; }
        
        /* Main Calculation Row */
        .calc-row {
            display: flex;
            border-bottom: 1px solid #ccc;
        }
        .calc-col {
            flex: 1;
            padding: 0;
        }
        .row-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 10px;
            border-right: 1px solid #000;
        }
        .row-item:last-child { border-right: none; }
      
        table.main-calc {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }
        table.main-calc th {
            background-color: #e0e0e0;
            padding: 10px;
            border: 1px solid #000;
            text-align: left;
        }
        table.main-calc td {
            border: 1px solid #000;
            padding: 0;
            vertical-align: top;
        }
        
        .item-row {
            width: 100%;
            padding: 5px 8px;
            border-bottom: 1px solid #eee;
        }
        .item-name { display: inline-block; width: 60%; }
        .item-amt { display: inline-block; width: 38%; text-align: right; font-family: 'Courier New', monospace; }

        .total-row td {
            background-color: #f9f9f9;
            font-weight: bold;
            padding: 10px;
            border-top: 2px solid #000;
        }

        .net-pay {
            text-align: right;
            font-weight: bold;
            font-size: 16px;
            margin-top: 20px;
            padding: 15px;
            border: 2px solid #000;
            background-color: #f0f0f0;
        }
      
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            padding-top: 10px;
            border-top: 1px solid #ccc;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-name">{{ $company_name }}</div>
            <div class="company-address">{{ $company_address }}</div>
            <div class="payslip-title">Payslip: {{ $payroll->pay_period ?? 'N/A' }}</div>
        </div>
      
        <!-- Employee Details -->
        <table class="info-table">
            <tr>
                <td class="label">Employee ID:</td>
                <td class="value">{{ $employee->employee_id ?? 'N/A' }}</td>
                <td class="label">Pay Date:</td>
                <td class="value">{{ $payslip->payment_date ? \Carbon\Carbon::parse($payslip->payment_date)->format('d M Y') : 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Name:</td>
                <td class="value">{{ $employee->user->first_name ?? '' }} {{ $employee->user->last_name ?? '' }}</td>
                <td class="label">Days Worked:</td>
                <td class="value">{{ $payroll->working_days ?? '22' }}</td>
            </tr>
            <tr>
                <td class="label">Department:</td>
                <td class="value">{{ $employee->department ?? 'N/A' }}</td>
                <td class="label">Position:</td>
                <td class="value">{{ $employee->position ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Type:</td>
                <td class="value">{{ ucfirst(str_replace('_', ' ', $employee->employment_type ?? 'N/A')) }}</td>
                <td class="label">Period:</td>
                <td class="value">
                    {{ $payslip->pay_period_start ? \Carbon\Carbon::parse($payslip->pay_period_start)->format('d M') : '' }} - 
                    {{ $payslip->pay_period_end ? \Carbon\Carbon::parse($payslip->pay_period_end)->format('d M Y') : '' }}
                </td>
            </tr>
        </table>
      
        <!-- Dynamic Earnings & Deductions Table -->
        <table class="main-calc">
            <thead>
                <tr>
                    <th width="50%">EARNINGS</th>
                    <th width="50%">DEDUCTIONS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($table_rows as $row)
                <tr>
                    <!-- Earning Column -->
                    <td>
                        @if($row['earning'])
                        <div class="item-row">
                            <span class="item-name">{{ $row['earning']['name'] }}</span>
                            <span class="item-amt">{{ number_format($row['earning']['amount'], 2) }}</span>
                        </div>
                        @else
                        <div class="item-row">&nbsp;</div>
                        @endif
                    </td>
                    
                    <!-- Deduction Column -->
                    <td>
                        @if($row['deduction'])
                        <div class="item-row">
                            <span class="item-name">{{ $row['deduction']['name'] }}</span>
                            <span class="item-amt">{{ number_format($row['deduction']['amount'], 2) }}</span>
                        </div>
                        @else
                        <div class="item-row">&nbsp;</div>
                        @endif
                    </td>
                </tr>
                @endforeach

                <!-- Totals -->
                <tr class="total-row">
                    <td>
                        <div class="item-row">
                            <span class="item-name">GROSS PAY</span>
                            <span class="item-amt">{{ number_format($payslip->gross_salary, 2) }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="item-row">
                            <span class="item-name">TOTAL DEDUCTIONS</span>
                            <span class="item-amt">{{ number_format($payslip->total_deductions, 2) }}</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
      
        <!-- Net Pay -->
        <div class="net-pay">
            NET PAY: K{{ number_format($payslip->net_pay, 2) }}
        </div>
      
        <!-- Footer -->
        <div class="footer">
            <p>Generated on {{ now()->format('d M Y H:i') }}</p>
            <p>This is a computer-generated document and needs no signature.</p>
        </div>
    </div>
</body>
</html>