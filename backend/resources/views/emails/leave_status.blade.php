<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Leave Status Update</title>
    <!-- Similar styles as provided -->
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Your Leave Request Update</h1>
        </div>
        <div class="content">
            <p>Hello {{ $notifiable->name }},</p>
            <p>Your leave request has been {{ $status }}.</p>
            <!-- Add details -->
        </div>
        <!-- Footer -->
    </div>
</body>
</html>