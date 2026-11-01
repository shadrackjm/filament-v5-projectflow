<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement(TaskStatus::cases());

        return [
            'project_id' => Project::factory(),
            'assignee_id' => null,
            'title' => Str::ucfirst(fake()->words(4, true)),
            'description' => fake()->optional()->paragraph(),
            'status' => $status,
            'priority' => fake()->randomElement(Priority::cases()),
            'estimated_hours' => fake()->randomFloat(1, 0.5, 40),
            'due_at' => fake()->dateTimeBetween('-1 month', '+3 months'),
            'is_billable' => fake()->boolean(75),
            'sort' => 0,
            'completed_at' => $status === TaskStatus::Done ? fake()->dateTimeBetween('-1 month') : null,
        ];
    }

    public function done(): static
    {
        return $this->state(fn (): array => [
            'status' => TaskStatus::Done,
            'completed_at' => now(),
        ]);
    }
}
