<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip - {{ $employee->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; color: #333; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
        .company-info { margin-bottom: 20px; }
        .employee-info { margin-bottom: 20px; }
        .section { margin-bottom: 20px; }
        .section-title { background: #f5f5f5; padding: 8px; font-weight: bold; border-bottom: 1px solid #ddd; }
        .row { display: flex; margin-bottom: 5px; }
        .col { flex: 1; padding: 5px; }
        .col-2 { flex: 2; }
        .col-3 { flex: 3; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background: #f5f5f5; }
        .total-row { font-weight: bold; background: #f9f9f9; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 10px; text-align: center; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>PAYSLIP</h1>
            <div class="company-info">
                <h2>{{ config('app.name', 'Payroll System') }}</h2>
                <p>Pay Period: {{ $payroll->pay_period }}</p>
                <p>Issue Date: {{ $payroll->processed_at->format('M d, Y') }}</p>
            </div>
        </div>

        <div class="employee-info">
            <div class="row">
                <div class="col">
                    <strong>Employee ID:</strong> {{ $employee->employee_id }}
                </div>
                <div class="col">
                    <strong>Name:</strong> {{ $employee->user->name }}
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <strong>Position:</strong> {{ $employee->position }}
                </div>
                <div class="col">
                    <strong>Department:</strong> {{ $employee->department }}
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Earnings</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Hours/Rate</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Basic Salary</td>
                        <td>{{ $payroll->working_days }} days</td>
                        <td class="text-right">ZMW{{ number_format($payslip->basic_salary, 2) }}</td>
                    </tr>
                    @if($payslip->overtime_hours > 0)
                    <tr>
                        <td>Overtime Pay</td>
                        <td>{{ $payslip->overtime_hours }} hours</td>
                        <td class="text-right">ZMW{{ number_format($payslip->overtime_pay, 2) }}</td>
                    </tr>
                    @endif
                    @foreach($payslip->bonuses as $bonus)
                    <tr>
                        <td>{{ $bonus->description }}</td>
                        <td>{{ $bonus->calculation_basis }}</td>
                        <td class="text-right">ZMW{{ number_format($bonus->amount, 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="2"><strong>Gross Pay</strong></td>
                        <td class="text-right"><strong>ZMW{{ number_format($payslip->gross_pay, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Deductions</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Type</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payslip->deductions as $deduction)
                    <tr>
                        <td>{{ $deduction->description }}</td>
                        <td>{{ $deduction->type }}</td>
                        <td class="text-right">ZMW{{ number_format($deduction->amount, 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="2"><strong>Total Deductions</strong></td>
                        <td class="text-right"><strong>ZMW{{ number_format($payslip->total_deductions, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Summary</div>
            <div style="background: #f9f9f9; padding: 15px; border-radius: 5px;">
                <div class="row">
                    <div class="col"><strong>Gross Pay:</strong></div>
                    <div class="col text-right">ZMW{{ number_format($payslip->gross_pay, 2) }}</div>
                </div>
                <div class="row">
                    <div class="col"><strong>Total Deductions:</strong></div>
                    <div class="col text-right">ZMW{{ number_format($payslip->total_deductions, 2) }}</div>
                </div>
                <div class="row" style="font-size: 14px; font-weight: bold; border-top: 1px solid #ddd; padding-top: 8px;">
                    <div class="col"><strong>Net Pay:</strong></div>
                    <div class="col text-right">ZMW{{ number_format($payslip->net_pay, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Year-to-Date Summary</div>
            <div class="row">
                <div class="col">
                    <strong>Gross Pay YTD:</strong> ZMW{{ number_format($payslip->ytd_gross_pay, 2) }}
                </div>
                <div class="col">
                    <strong>Tax Paid YTD:</strong> ZMW{{ number_format($payslip->ytd_tax, 2) }}
                </div>
                <div class="col">
                    <strong>Net Pay YTD:</strong> ZMW{{ number_format($payslip->ytd_net_pay, 2) }}
                </div>
            </div>
        </div>

        <div class="footer">
            <p>This payslip is computer generated and does not require a signature.</p>
            <p>Confidential - For Employee Use Only</p>
        </div>
    </div>
</body>
</html>