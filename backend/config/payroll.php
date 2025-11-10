<?php

return [
    'leave' => [
        'types' => [
            'annual' => 'Annual Leave',
            'sick' => 'Sick Leave',
            'maternity' => 'Maternity Leave',
            'paternity' => 'Paternity Leave',
            'bereavement' => 'Bereavement Leave',
            'unpaid' => 'Unpaid Leave',
        ],
        
        'default_balances' => [
            'annual' => 21.00,
            'sick' => 10.00,
            'maternity' => 30.00,
            'paternity' => 10.00,
            'bereavement' => 5.00,
            'unpaid' => 0.00,
        ],
        
        'max_days_per_request' => 30,
        'min_advance_days' => 0, // Minimum days in advance to request leave
    ],
];