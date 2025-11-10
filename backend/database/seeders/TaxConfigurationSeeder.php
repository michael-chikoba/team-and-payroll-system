<?php

namespace Database\Seeders;

use App\Models\TaxConfiguration;
use Illuminate\Database\Seeder;

class TaxConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $taxBrackets = [
            ['min' => 0, 'max' => 11000, 'rate' => 10],
            ['min' => 11001, 'max' => 44725, 'rate' => 12],
            ['min' => 44726, 'max' => 95375, 'rate' => 22],
            ['min' => 95376, 'max' => 182100, 'rate' => 24],
            ['min' => 182101, 'max' => 231250, 'rate' => 32],
            ['min' => 231251, 'max' => 578125, 'rate' => 35],
            ['min' => 578126, 'max' => null, 'rate' => 37],
        ];

        TaxConfiguration::create([
            'country' => 'ZM',
            'state' => 'LSK',
            'tax_brackets' => $taxBrackets,
            'social_security_rate' => 6.2,
            'medicare_rate' => 1.45,
            'is_active' => true,
        ]);

        // Add more tax configurations for different states if needed
        TaxConfiguration::create([
            'country' => 'ZM',
            'state' => 'LSK',
            'tax_brackets' => $taxBrackets,
            'social_security_rate' => 6.2,
            'medicare_rate' => 1.45,
            'is_active' => false,
        ]);
    }
}