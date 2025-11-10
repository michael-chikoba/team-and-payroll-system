<?php

namespace Database\Factories;

use App\Models\Leave;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveFactory extends Factory
{
    protected $model = Leave::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-30 days', '+30 days');
        $endDate = $this->faker->dateTimeBetween($startDate, $startDate->format('Y-m-d') . ' +7 days');
        $totalDays = $startDate->diff($endDate)->days + 1;

        return [
            'employee_id' => Employee::factory(),
            'manager_id' => User::where('role', 'manager')->inRandomOrder()->first()?->id,
            'type' => $this->faker->randomElement(['vacation', 'sick', 'personal']),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'reason' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'manager_notes' => $this->faker->optional()->paragraph(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    public function vacation(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'vacation',
        ]);
    }

    public function sick(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'sick',
        ]);
    }

    public function personal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'personal',
        ]);
    }
}