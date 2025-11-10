<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip Available</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2196F3; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        .summary { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Your Payslip is Ready</h1>
        </div>
        <div class="content">
            <p>Hello {{ $employeeName }},</p>
            <p>Your payslip for {{ $payPeriod }} is now available.</p>
            
            <div class="summary">
                <h3>Payment Summary:</h3>
                <p><strong>Gross Pay:</strong> ${{ number_format($grossPay, 2) }}</p>
                <p><strong>Net Pay:</strong> ${{ number_format($netPay, 2) }}</p>
                <p><strong>Pay Date:</strong> {{ $payDate }}</p>
            </div>
            
            <p>You can view and download your payslip by logging into the payroll system.</p>
            
            <div style="text-align: center; margin: 20px 0;">
                <a href="{{ $payslipUrl }}" style="background: #2196F3; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;">
                    View Payslip
                </a>
            </div>
            
            <p>If you have any questions about your payslip, please contact the HR department.</p>
        </div>
        <div class="footer">
            <p>This is an automated message from {{ config('app.name') }}</p>
            <p>Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>