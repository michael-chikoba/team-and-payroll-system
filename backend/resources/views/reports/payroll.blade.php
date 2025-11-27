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
        .deductions-breakdown {
            background: #fff7ed;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #ea580c;
        }
        .deductions-breakdown h3 {
            color: #9a3412;
            margin-top: 0;
            font-size: 16px;
        }
        .deductions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }
        .deduction-item {
            background: white;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #fed7aa;
        }
        .deduction-item label {
            display: block;
            font-size: 10px;
            color: #92400e;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .deduction-item value {
            font-size: 14px;
            font-weight: bold;
            color: #c2410c;
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
        .text-right {
            text-align: right;
        }
        .section-title {
            color: #1f2937;
            font-size: 16px;
            margin: 25px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
        }
        @media print {
            body { margin: 0; }
            .header { page-break-after: avoid; }
            .summary { page-break-inside: avoid; }
            .deductions-breakdown { page-break-inside: avoid; }
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
            <div class="summary-item">
                <label>Total Other Deductions</label>
                <value class="currency">ZMW {{ number_format($report['total_other_deductions'] ?? 0, 2) }}</value>
            </div>
            <div class="summary-item">
                <label>Total Gross Salary</label>
                <value class="currency">ZMW {{ number_format($report['total_gross_salary'] ?? 0, 2) }}</value>
            </div>
        </div>
    </div>

    <!-- Deductions Breakdown Section -->
    <div class="deductions-breakdown">
        <h3>Deductions Breakdown</h3>
        <div class="deductions-grid">
            <div class="deduction-item">
                <label>Total PAYE Tax</label>
                <value class="currency">ZMW {{ number_format($report['total_paye_tax'] ?? $report['total_tax_amount'], 2) }}</value>
            </div>
            <div class="deduction-item">
                <label>Total NAPSA</label>
                <value class="currency">ZMW {{ number_format($report['total_napsa'] ?? 0, 2) }}</value>
            </div>
            <div class="deduction-item">
                <label>Total NHIMA</label>
                <value class="currency">ZMW {{ number_format($report['total_nhima'] ?? 0, 2) }}</value>
            </div>
            <div class="deduction-item">
                <label>Total Other Deductions</label>
                <value class="currency">ZMW {{ number_format($report['total_other_deductions'] ?? 0, 2) }}</value>
            </div>
            <div class="deduction-item">
                <label>Total All Deductions</label>
                <value class="currency">ZMW {{ number_format($report['total_all_deductions'] ?? 0, 2) }}</value>
            </div>
        </div>
    </div>

    <!-- Detailed Payslip Table -->
    <div class="details">
        <h2 class="section-title">Payslip Details</h2>
        <table>
            <thead>
                <tr>
                    <th>Employee Name</th>
                    <th class="text-right">Gross Salary</th>
                    <th class="text-right">PAYE Tax</th>
                    <th class="text-right">NAPSA</th>
                    <th class="text-right">NHIMA</th>
                    <th class="text-right">Other Deductions</th>
                    <th class="text-right">Total Deductions</th>
                    <th class="text-right">Net Salary</th>
                    <th>Pay Period</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['payslip_details'] as $detail)
                    <tr>
                        <td>{{ $detail['employee_name'] }}</td>
                        <td class="currency text-right">ZMW {{ number_format($detail['gross_salary'], 2) }}</td>
                        <td class="currency text-right">ZMW {{ number_format($detail['paye_tax'] ?? $detail['tax_amount'] ?? 0, 2) }}</td>
                        <td class="currency text-right">ZMW {{ number_format($detail['napsa'] ?? 0, 2) }}</td>
                        <td class="currency text-right">ZMW {{ number_format($detail['nhima'] ?? 0, 2) }}</td>
                        <td class="currency text-right">ZMW {{ number_format($detail['other_deductions'] ?? 0, 2) }}</td>
                        <td class="currency text-right">ZMW {{ number_format($detail['total_deductions'] ?? $detail['deductions'], 2) }}</td>
                        <td class="currency text-right">ZMW {{ number_format($detail['net_salary'], 2) }}</td>
                        <td>{{ $detail['pay_period'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; color: #9ca3af;">No payslip details available for this period.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="background: #f3f4f6; font-weight: bold;">
                    <td>Totals</td>
                    <td class="currency text-right">ZMW {{ number_format($report['total_gross_salary'] ?? array_sum(array_column($report['payslip_details'], 'gross_salary')), 2) }}</td>
                    <td class="currency text-right">ZMW {{ number_format($report['total_paye_tax'] ?? $report['total_tax_amount'] ?? 0, 2) }}</td>
                    <td class="currency text-right">ZMW {{ number_format($report['total_napsa'] ?? 0, 2) }}</td>
                    <td class="currency text-right">ZMW {{ number_format($report['total_nhima'] ?? 0, 2) }}</td>
                    <td class="currency text-right">ZMW {{ number_format($report['total_other_deductions'] ?? 0, 2) }}</td>
                    <td class="currency text-right">ZMW {{ number_format($report['total_all_deductions'] ?? array_sum(array_column($report['payslip_details'], 'deductions')), 2) }}</td>
                    <td class="currency text-right">ZMW {{ number_format($report['total_net_salary'], 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Other Deductions Breakdown (if available) -->
    @if(isset($report['other_deductions_breakdown']) && count($report['other_deductions_breakdown']) > 0)
    <div class="details">
        <h2 class="section-title">Other Deductions Breakdown</h2>
        <table>
            <thead>
                <tr>
                    <th>Deduction Type</th>
                    <th class="text-right">Amount</th>
                    <th class="text-right">Number of Employees</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['other_deductions_breakdown'] as $deduction)
                    <tr>
                        <td>{{ $deduction['type'] ?? 'Other' }}</td>
                        <td class="currency text-right">ZMW {{ number_format($deduction['amount'] ?? 0, 2) }}</td>
                        <td class="text-right">{{ $deduction['employee_count'] ?? 1 }}</td>
                        <td>{{ $deduction['description'] ?? 'Additional deductions' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>This report was generated by the HR Management System. For questions, contact HR Department.</p>
        <p style="margin-top: 10px;">All amounts are in Zambian Kwacha (ZMW)</p>
        <p style="margin-top: 5px; font-size: 9px;">
            Other deductions include: Loan deductions, advance repayments, insurance premiums, union dues, and other voluntary deductions.
        </p>
    </div>
</body>
</html>