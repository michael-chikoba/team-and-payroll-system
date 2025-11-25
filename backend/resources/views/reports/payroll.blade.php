{{-- resources/views/reports/payroll.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Report</title>
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
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .summary {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .summary h2 {
            color: #1f2937;
            margin-top: 0;
            font-size: 18px;
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
            border-left: 4px solid #3b82f6;
        }
        .summary-item label {
            display: block;
            font-size: 11px;
            color: #9ca3af;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .summary-item value {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
        }
        .details {
            margin-top: 30px;
        }
        .details h2 {
            color: #1f2937;
            font-size: 18px;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f9fafb;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
            font-size: 10px;
        }
        tr:hover {
            background: #f9fafb;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .currency {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        @media print {
            body { margin: 0; }
            .header { page-break-after: avoid; }
            .summary { page-break-inside: avoid; }
            table { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Payroll Report</h1>
        <p>Generated on: {{ now()->format('F d, Y - H:i') }}</p>
        <p>Period: {{ \Carbon\Carbon::parse($report['period_start'])->format('F d, Y') }} to {{ \Carbon\Carbon::parse($report['period_end'])->format('F d, Y') }}</p>
    </div>
    <div class="summary">
        <h2>Summary</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <label>Total Payroll Amount</label>
                <value class="currency">ZMW {{ number_format($report['total_net_salary'], 2) }}</value>
            </div>
            <div class="summary-item">
                <label>Employees Processed</label>
                <value>{{ $report['processed_employees'] }}</value>
            </div>
            <div class="summary-item">
                <label>Average Net Salary</label>
                <value class="currency">ZMW {{ number_format($report['average_net_salary'], 2) }}</value>
            </div>
            <div class="summary-item">
                <label>Total Tax Withheld</label>
                <value class="currency">ZMW {{ number_format($report['total_tax_amount'], 2) }}</value>
            </div>
        </div>
    </div>
    <div class="details">
        <h2>Payslip Details</h2>
        <table>
            <thead>
                <tr>
                    <th>Employee Name</th>
                    <th>Gross Salary</th>
                    <th>Total Deductions</th>
                    <th>Net Salary</th>
                    <th>Tax Amount</th>
                    <th>Pay Period</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['payslip_details'] as $detail)
                    <tr>
                        <td>{{ $detail['employee_name'] }}</td>
                        <td class="currency">ZMW {{ number_format($detail['gross_salary'], 2) }}</td>
                        <td class="currency">ZMW {{ number_format($detail['deductions'], 2) }}</td>
                        <td class="currency">ZMW {{ number_format($detail['net_salary'], 2) }}</td>
                        <td class="currency">ZMW {{ number_format($detail['tax_amount'], 2) }}</td>
                        <td>{{ $detail['pay_period'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #9ca3af;">No payslip details available for this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="footer">
        <p>This report was generated by the HR Management System. For questions, contact HR Department.</p>
        <p style="margin-top: 10px;">All amounts are in Zambian Kwacha (ZMW)</p>
    </div>
</body>
</html>