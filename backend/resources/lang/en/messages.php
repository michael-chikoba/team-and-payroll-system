<?php

return [
    'welcome' => 'Welcome to Payroll System',
    'login_success' => 'Login successful',
    'logout_success' => 'Logout successful',
    'registration_success' => 'Registration successful',
    'leave_submitted' => 'Leave request submitted successfully',
    'leave_approved' => 'Leave request approved',
    'leave_rejected' => 'Leave request rejected',
    'attendance_logged' => 'Attendance logged successfully',
    'payroll_processed' => 'Payroll processed successfully',
    'payslip_generated' => 'Payslip generated successfully',
    'profile_updated' => 'Profile updated successfully',
    'password_updated' => 'Password updated successfully',
    
    'errors' => [
        'unauthorized' => 'Unauthorized access',
        'forbidden' => 'Access forbidden',
        'not_found' => 'Resource not found',
        'validation_error' => 'Validation error',
        'server_error' => 'Server error',
        'leave_balance_insufficient' => 'Insufficient leave balance',
        'attendance_already_logged' => 'Attendance already logged for today',
        'payroll_already_processed' => 'Payroll already processed for this period',
    ],
    
    'notifications' => [
        'leave_submitted_subject' => 'New Leave Request Submitted',
        'leave_approved_subject' => 'Leave Request Approved',
        'leave_rejected_subject' => 'Leave Request Not Approved',
        'payslip_ready_subject' => 'Your Payslip is Ready',
        'payroll_processed_subject' => 'Payroll Processing Complete',
    ],
    
    'email' => [
        'greeting' => 'Hello :name,',
        'regards' => 'Regards,',
        'footer' => 'If you\'re having trouble clicking the ":actionText" button, copy and paste the URL below into your web browser:',
    ],
];