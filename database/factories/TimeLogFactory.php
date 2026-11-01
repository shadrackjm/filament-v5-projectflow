<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeLog>
 */
class TimeLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'minutes' => fake()->numberBetween(15, 480),
            'logged_on' => fake()->dateTimeBetween('-2 months'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
