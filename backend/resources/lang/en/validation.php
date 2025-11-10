<?php

return [
    'accepted' => 'The :attribute must be accepted.',
    'active_url' => 'The :attribute is not a valid URL.',
    'after' => 'The :attribute must be a date after :date.',
    'after_or_equal' => 'The :attribute must be a date after or equal to :date.',
    'alpha' => 'The :attribute must only contain letters.',
    'alpha_dash' => 'The :attribute must only contain letters, numbers, dashes and underscores.',
    'alpha_num' => 'The :attribute must only contain letters and numbers.',
    'array' => 'The :attribute must be an array.',
    'before' => 'The :attribute must be a date before :date.',
    'before_or_equal' => 'The :attribute must be a date before or equal to :date.',
    
    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
        'email' => [
            'unique' => 'This email address is already registered.',
        ],
        'employee_id' => [
            'unique' => 'This employee ID is already in use.',
        ],
    ],

    'attributes' => [
        'name' => 'name',
        'email' => 'email address',
        'password' => 'password',
        'role' => 'role',
        'position' => 'position',
        'department' => 'department',
        'base_salary' => 'base salary',
        'hire_date' => 'hire date',
        'employment_type' => 'employment type',
        'start_date' => 'start date',
        'end_date' => 'end date',
        'reason' => 'reason',
        'type' => 'type',
        'status' => 'status',
        'clock_in' => 'clock in time',
        'clock_out' => 'clock out time',
        'date' => 'date',
    ],
];