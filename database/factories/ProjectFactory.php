<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\ProjectStatus;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(3, true));
        $startsAt = fake()->dateTimeBetween('-6 months', '+1 month');

        return [
            'team_id' => Team::factory(),
            'client_id' => null,
            'lead_id' => null,
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(ProjectStatus::cases()),
            'priority' => fake()->randomElement(Priority::cases()),
            'color' => fake()->randomElement(['#3b82f6', '#8b5cf6', '#ec4899', '#f97316', '#10b981']),
            'budget' => fake()->randomFloat(2, 2_000, 90_000),
            'starts_at' => $startsAt,
            'due_at' => fake()->dateTimeBetween($startsAt, '+8 months'),
            'is_archived' => false,
        ];
    }
}
