<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('-30 days', 'now');
        $clockIn = $this->faker->dateTimeBetween($date->format('Y-m-d') . ' 08:00:00', $date->format('Y-m-d') . ' 09:30:00');
        $clockOut = $this->faker->dateTimeBetween($date->format('Y-m-d') . ' 16:00:00', $date->format('Y-m-d') . ' 18:00:00');
        
        $totalHours = ($clockOut->getTimestamp() - $clockIn->getTimestamp()) / 3600;
        $totalHours = max(0, $totalHours - 1); // Subtract 1 hour for break

        return [
            'employee_id' => Employee::factory(),
            'date' => $date,
            'clock_in' => $clockIn->format('H:i:s'),
            'clock_out' => $clockOut->format('H:i:s'),
            'total_hours' => round($totalHours, 2),
            'break_minutes' => 60,
            'status' => $this->faker->randomElement(['present', 'present', 'present', 'late']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function present(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'present',
        ]);
    }

    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'absent',
            'clock_in' => null,
            'clock_out' => null,
            'total_hours' => 0,
        ]);
    }

    public function late(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'late',
        ]);
    }
}