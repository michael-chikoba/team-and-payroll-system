<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip - {{ $employee->user->first_name ?? '' }} {{ $employee->user->last_name ?? '' }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
            background: #fff;
        }

        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }

        /* --- Header Section --- */
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #444;
            padding-bottom: 15px;
        }
        .company-name {
            font-weight: 800;
            font-size: 22px;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .company-address {
            font-size: 11px;
            color: #666;
            margin-bottom: 5px;
        }
        .payslip-title {
            font-weight: bold;
            font-size: 16px;
            margin-top: 15px;
            text-transform: uppercase;
        }

        /* --- Employee Info Table --- */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .info-table td {
            padding: 6px 10px;
            vertical-align: top;
            border-bottom: 1px solid #eee;
        }
        .info-table td.label {
            font-weight: bold;
            width: 15%;
            color: #555;
            white-space: nowrap;
        }
        .info-table td.value {
            width: 35%;
            font-weight: 500;
        }

        /* --- Main Calculation Table --- */
        .main-calc {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #333;
            margin-bottom: 20px;
        }

        /* Headings */
        .main-calc th {
            background-color: #eee;
            color: #000;
            padding: 12px 10px;
            border: 1px solid #333;
            text-align: left;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            width: 50%; /* Strictly 50/50 split */
        }

        .main-calc td {
            border: 1px solid #333;
            vertical-align: top;
            padding: 0; /* Padding handled by inner divs */
        }

        /* --- Row Item Styling --- */
        .item-row {
            display: table;
            width: 100%;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .item-row:last-child {
            border-bottom: none;
        }

        .item-name {
            display: table-cell;
            padding: 8px 10px;
            text-align: left;
            vertical-align: middle;
            color: #444;
        }

        .item-amt {
            display: table-cell;
            padding: 8px 10px;
            text-align: right;
            vertical-align: middle;
            font-family: 'Courier New', monospace; /* Monospace for alignment */
            font-weight: bold;
            color: #000;
            white-space: nowrap; /* Prevents decimals from breaking line */
            width: 1%;
        }

        /* Empty state helper */
        .empty-row {
            padding: 8px 10px;
        }

        /* --- Totals Row --- */
        .total-wrapper {
            background-color: #fafafa;
            border-top: 2px solid #333;
        }
        .total-wrapper .item-name {
            font-weight: 800;
            text-transform: uppercase;
            font-size: 12px;
        }
        .total-wrapper .item-amt {
            font-size: 13px;
        }

        /* --- Net Pay Box --- */
        .net-pay-container {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
        }
        .net-pay {
            text-align: right;
            font-weight: bold;
            font-size: 16px;
            padding: 15px 30px;
            border: 2px solid #333;
            background-color: #eee;
            min-width: 200px;
        }
        .net-pay span {
            display: block;
            font-size: 11px;
            color: #666;
            margin-bottom: 5px;
            font-weight: normal;
        }

        /* --- Footer --- */
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #999;
            padding-top: 10px;
            border-top: 1px solid #eee;
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
                <td class="label">Pay Period:</td>
                <td class="value">
                    {{ $payslip->pay_period_start ? \Carbon\Carbon::parse($payslip->pay_period_start)->format('d M') : '' }} - 
                    {{ $payslip->pay_period_end ? \Carbon\Carbon::parse($payslip->pay_period_end)->format('d M Y') : '' }}
                </td>
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
                <!-- Empty cells to maintain table structure -->
                <td class="label">&nbsp;</td>
                <td class="value">&nbsp;</td>
            </tr>
        </table>

        <!-- Earnings & Deductions Table -->
        <table class="main-calc">
            <thead>
                <tr>
                    <th>EARNINGS</th>
                    <th>DEDUCTIONS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($table_rows as $row)
                <tr>
                    <!-- Earning Column -->
                    <td>
                        @if($row['earning'])
                        <div class="item-row">
                            <div class="item-name">{{ $row['earning']['name'] }}</div>
                            <div class="item-amt">{{ number_format($row['earning']['amount'], 2) }}</div>
                        </div>
                        @else
                        <div class="item-row empty-row">&nbsp;</div>
                        @endif
                    </td>
                    
                    <!-- Deduction Column -->
                    <td>
                        @if($row['deduction'])
                        <div class="item-row">
                            <div class="item-name">{{ $row['deduction']['name'] }}</div>
                            <div class="item-amt">{{ number_format($row['deduction']['amount'], 2) }}</div>
                        </div>
                        @else
                        <div class="item-row empty-row">&nbsp;</div>
                        @endif
                    </td>
                </tr>
                @endforeach

                <!-- Totals Row -->
                <tr>
                    <td class="total-wrapper">
                        <div class="item-row" style="border: none;">
                            <div class="item-name">Total Earnings</div>
                            <div class="item-amt">{{ number_format($payslip->gross_salary, 2) }}</div>
                        </div>
                    </td>
                    <td class="total-wrapper">
                        <div class="item-row" style="border: none;">
                            <div class="item-name">Total Deductions</div>
                            <div class="item-amt">{{ number_format($payslip->total_deductions, 2) }}</div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Net Pay -->
        <div class="net-pay-container">
            <div class="net-pay">
                <span>NET SALARY PAYABLE</span>
                K{{ number_format($payslip->net_pay, 2) }}
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Generated on {{ now()->format('d M Y H:i') }}</p>
            <p>This is a computer-generated document and needs no signature.</p>
        </div>
    </div>
</body>
</html>