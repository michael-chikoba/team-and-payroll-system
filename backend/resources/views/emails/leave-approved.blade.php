<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Leave Request Approved</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Leave Request Approved</h1>
        </div>
        <div class="content">
            <p>Hello {{ $employeeName }},</p>
            <p>Your leave request has been approved by your manager.</p>
            
            <div style="background: white; padding: 15px; border-radius: 5px; margin: 15px 0;">
                <h3>Leave Details:</h3>
                <p><strong>Type:</strong> {{ $leaveType }}</p>
                <p><strong>From:</strong> {{ $startDate }}</p>
                <p><strong>To:</strong> {{ $endDate }}</p>
                <p><strong>Total Days:</strong> {{ $totalDays }}</p>
                @if($reason)
                <p><strong>Reason:</strong> {{ $reason }}</p>
                @endif
            </div>
            
            <p>Your leave balance has been updated accordingly.</p>
            <p>If you have any questions, please contact HR.</p>
        </div>
        <div class="footer">
            <p>This is an automated message from {{ config('app.name') }}</p>
        </div>
    </div>
</body>
</html>