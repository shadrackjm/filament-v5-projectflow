<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Models\Client;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the ProjectFlow demo data.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Ada Lovelace',
            'email' => 'admin@projectflow.test',
            'job_title' => 'Studio Director',
            'is_admin' => true,
        ]);

        $teammates = User::factory(7)->create();
        $everyone = $teammates->push($admin);

        collect(['Northwind Studio', 'Blue Harbour Labs'])->each(function (string $name) use ($everyone, $admin) {
            $team = \App\Models\Team::factory()->create([
                'name' => $name,
                'slug' => str($name)->slug()->value(),
            ]);

            $team->members()->attach($admin, ['role' => TeamRole::Owner->value]);
            $everyone->where('id', '!=', $admin->id)->random(4)->each(
                fn (User $user) => $team->members()->attach($user, ['role' => TeamRole::Member->value])
            );

            $members = $team->members()->pluck('users.id');

            $tags = collect(['design', 'backend', 'urgent', 'client-review', 'infra'])
                ->map(fn (string $tag) => Tag::factory()->create([
                    'team_id' => $team->id,
                    'name' => $tag,
                ]));

            $clients = Client::factory(4)->create(['team_id' => $team->id]);

            Project::factory(6)->create([
                'team_id' => $team->id,
            ])->each(function (Project $project) use ($clients, $members, $tags) {
                $project->update([
                    'client_id' => $clients->random()->id,
                    'lead_id' => $members->random(),
                ]);

                Task::factory(random_int(6, 14))->create([
                    'project_id' => $project->id,
                    'assignee_id' => $members->random(),
                ])->each(function (Task $task) use ($members, $tags) {
                    $task->tags()->attach($tags->random(random_int(1, 2))->pluck('id'));

                    Comment::factory(random_int(0, 3))->create([
                        'task_id' => $task->id,
                        'user_id' => $members->random(),
                    ]);

                    TimeLog::factory(random_int(0, 4))->create([
                        'task_id' => $task->id,
                        'user_id' => $members->random(),
                    ]);
                });
            });
        });
    }
}
