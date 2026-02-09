<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket Assigned</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4F46E5; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
        .ticket-info { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #4F46E5; }
        .button { display: inline-block; background: #4F46E5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎫 Ticket Assignment</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $assignedUser->first_name }},</p>
            
            <p>You have been assigned to a ticket as <strong>{{ $roleLabel }}</strong>.</p>
            
            <div class="ticket-info">
                <h3>Ticket Details</h3>
                <p><strong>Ticket #:</strong> {{ $ticket->id }}</p>
                <p><strong>Title:</strong> {{ $ticket->title }}</p>
                <p><strong>Type:</strong> {{ ucfirst($ticket->type) }}</p>
                <p><strong>Priority:</strong> <span style="color: {{ $ticket->priority === 'critical' ? '#DC2626' : ($ticket->priority === 'high' ? '#EA580C' : ($ticket->priority === 'medium' ? '#D97706' : '#059669')) }}">{{ ucfirst($ticket->priority) }}</span></p>
                <p><strong>Status:</strong> {{ ucfirst($ticket->status) }}</p>
                <p><strong>Created By:</strong> {{ $ticket->user->first_name }} {{ $ticket->user->last_name }}</p>
                <p><strong>Due Date:</strong> {{ $ticket->due_date ? $ticket->due_date->format('M d, Y') : 'Not set' }}</p>
            </div>
            
            <p><strong>Your Role:</strong> {{ $roleLabel }}</p>
            
            <p>Please review the ticket and take appropriate action as needed.</p>
            
            <a href="{{ url('/tickets/' . $ticket->id) }}" class="button">View Ticket</a>
            
            <div class="footer">
                <p>This is an automated notification from the Ticket Management System.</p>
                <p>If you believe this assignment was made in error, please contact your administrator.</p>
            </div>
        </div>
    </div>
</body>
</html>