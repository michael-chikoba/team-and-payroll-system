<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\CountryConfiguration;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            // -----------------------------
            //  ZAMBIA
            // -----------------------------
            [
                'code' => 'ZM',
                'name' => 'Zambia',
                'currency_code' => 'ZMW',
                'currency_symbol' => 'K',
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'timezone' => 'Africa/Lusaka',
                'phone_code' => '+260',
                'is_active' => true,
                'configuration' => [
                    'working_hours_per_day' => 8.00,
                    'working_days_per_week' => 5,
                    'working_days' => [1, 2, 3, 4, 5],
                    'work_start_time' => '08:00:00',
                    'work_end_time' => '17:00:00',
                    'overtime_multiplier' => 1.5,
                    'weekend_multiplier' => 2.0,
                    'holiday_multiplier' => 2.5,
                    'payroll_frequency' => 'monthly',
                    'payroll_day' => 25,
                    'tax_calculation_type' => 'progressive',
                    'tax_brackets' => [
                        ['min' => 0, 'max' => 4800, 'rate' => 0],
                        ['min' => 4801, 'max' => 6900, 'rate' => 20],
                        ['min' => 6901, 'max' => 9900, 'rate' => 30],
                        ['min' => 9901, 'max' => PHP_FLOAT_MAX, 'rate' => 37.5],
                    ],
                    'statutory_deductions' => [
                        ['name' => 'NAPSA', 'type' => 'percentage', 'value' => 5.0, 'cap' => null],
                        ['name' => 'NHIMA', 'type' => 'percentage', 'value' => 1.0, 'cap' => null],
                    ],
                    'annual_leave_days' => 24,
                    'sick_leave_days' => 12,
                    'maternity_leave_days' => 90,
                    'paternity_leave_days' => 14,
                    'public_holidays' => [
                        ['name' => 'New Year\'s Day', 'date' => '2025-01-01'],
                        ['name' => 'Youth Day', 'date' => '2025-03-12'],
                        ['name' => 'Good Friday', 'date' => '2025-04-18'],
                        ['name' => 'Easter Monday', 'date' => '2025-04-21'],
                        ['name' => 'Labour Day', 'date' => '2025-05-01'],
                        ['name' => 'Africa Freedom Day', 'date' => '2025-05-25'],
                        ['name' => 'Heroes Day', 'date' => '2025-07-07'],
                        ['name' => 'Unity Day', 'date' => '2025-07-08'],
                        ['name' => 'Farmers\' Day', 'date' => '2025-08-04'],
                        ['name' => 'Independence Day', 'date' => '2025-10-24'],
                        ['name' => 'Christmas Day', 'date' => '2025-12-25'],
                    ],
                    'minimum_wage' => '1500.00',
                ],
            ],

            // -----------------------------
            //  CONGO–BRAZZAVILLE
            // -----------------------------
            [
                'code' => 'CG',
                'name' => 'Congo-Brazzaville',
                'currency_code' => 'XAF',
                'currency_symbol' => 'FCFA',
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'timezone' => 'Africa/Brazzaville',
                'phone_code' => '+242',
                'is_active' => true,
                'configuration' => [
                    'working_hours_per_day' => 8.00,
                    'working_days_per_week' => 5,
                    'working_days' => [1, 2, 3, 4, 5],
                    'work_start_time' => '08:00:00',
                    'work_end_time' => '17:00:00',
                    'overtime_multiplier' => 1.5,
                    'weekend_multiplier' => 2.0,
                    'holiday_multiplier' => 2.0,
                    'payroll_frequency' => 'monthly',
                    'payroll_day' => 25,
                    'tax_calculation_type' => 'progressive',
                    'tax_brackets' => [
                        ['min' => 0, 'max' => 80000, 'rate' => 1],
                        ['min' => 80001, 'max' => 120000, 'rate' => 10],
                        ['min' => 120001, 'max' => 200000, 'rate' => 15],
                        ['min' => 200001, 'max' => PHP_FLOAT_MAX, 'rate' => 20],
                    ],
                    'statutory_deductions' => [
                        ['name' => 'CNSS', 'type' => 'percentage', 'value' => 4.0, 'cap' => null],
                    ],
                    'annual_leave_days' => 26,
                    'sick_leave_days' => 12,
                    'maternity_leave_days' => 98,
                    'paternity_leave_days' => 10,
                    'public_holidays' => [
                        ['name' => 'New Year\'s Day', 'date' => '2025-01-01'],
                        ['name' => 'Labour Day', 'date' => '2025-05-01'],
                        ['name' => 'Independence Day', 'date' => '2025-08-15'],
                        ['name' => 'All Saints Day', 'date' => '2025-11-01'],
                        ['name' => 'Christmas Day', 'date' => '2025-12-25'],
                    ],
                    'minimum_wage' => '90000.00',
                ],
            ],

            // -----------------------------
            //  NAMIBIA
            // -----------------------------
            [
                'code' => 'NA',
                'name' => 'Namibia',
                'currency_code' => 'NAD',
                'currency_symbol' => 'N$',
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'timezone' => 'Africa/Windhoek',
                'phone_code' => '+264',
                'is_active' => true,
                'configuration' => [
                    'working_hours_per_day' => 8.00,
                    'working_days_per_week' => 5,
                    'working_days' => [1, 2, 3, 4, 5],
                    'work_start_time' => '08:00:00',
                    'work_end_time' => '17:00:00',
                    'overtime_multiplier' => 1.5,
                    'weekend_multiplier' => 2.0,
                    'holiday_multiplier' => 2.0,
                    'payroll_frequency' => 'monthly',
                    'payroll_day' => 25,
                    'tax_calculation_type' => 'progressive',
                    'tax_brackets' => [
                        ['min' => 0, 'max' => 50000, 'rate' => 0],
                        ['min' => 50001, 'max' => 100000, 'rate' => 18],
                        ['min' => 100001, 'max' => 300000, 'rate' => 25],
                        ['min' => 300001, 'max' => PHP_FLOAT_MAX, 'rate' => 37],
                    ],
                    'statutory_deductions' => [
                        ['name' => 'Social Security', 'type' => 'percentage', 'value' => 0.9, 'cap' => 81.00],
                    ],
                    'annual_leave_days' => 24,
                    'sick_leave_days' => 30,
                    'maternity_leave_days' => 84,
                    'paternity_leave_days' => 0,
                    'public_holidays' => [
                        ['name' => 'New Year\'s Day', 'date' => '2025-01-01'],
                        ['name' => 'Independence Day', 'date' => '2025-03-21'],
                        ['name' => 'Cassinga Day', 'date' => '2025-05-04'],
                        ['name' => 'Africa Day', 'date' => '2025-05-25'],
                        ['name' => 'Heroes Day', 'date' => '2025-08-26'],
                        ['name' => 'Christmas Day', 'date' => '2025-12-25'],
                        ['name' => 'Family Day', 'date' => '2025-12-26'],
                    ],
                    'minimum_wage' => '1300.00',
                ],
            ],
        ];

        foreach ($countries as $countryData) {
            $configuration = $countryData['configuration'];
            unset($countryData['configuration']);

            // Use updateOrCreate to avoid duplicate entry errors
            $country = Country::updateOrCreate(
                ['code' => $countryData['code']], // Match on country code
                $countryData // Update/Create with this data
            );

            // Update or create the configuration
            $configuration['country_id'] = $country->id;
            CountryConfiguration::updateOrCreate(
                ['country_id' => $country->id],
                $configuration
            );
        }
    }
}