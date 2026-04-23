<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskBatchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'member']);
    }

    public function test_can_batch_update_status(): void
    {
        $tasks = Task::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson('/api/v1/tasks/batch-status', [
                'ids' => $tasks->pluck('id')->toArray(),
                'status' => 'done',
            ]);

        $response->assertOk()
            ->assertJson(['message' => 'Tasks updated successfully.']);

        foreach ($tasks as $task) {
            $this->assertDatabaseHas('tasks', [
                'id' => $task->id,
                'status' => 'done',
            ]);
        }
    }

    public function test_batch_creates_activity_logs(): void
    {
        $tasks = Task::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->user)
            ->patchJson('/api/v1/tasks/batch-status', [
                'ids' => $tasks->pluck('id')->toArray(),
                'status' => 'done',
            ]);

        foreach ($tasks as $task) {
            $this->assertDatabaseHas('activity_logs', [
                'task_id' => $task->id,
                'action' => 'status_changed',
            ]);
        }
    }

    public function test_can_get_activity_log(): void
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->patchJson('/api/v1/tasks/batch-status', [
                'ids' => [$task->id],
                'status' => 'in_progress',
            ]);

        $this->actingAs($this->user)
            ->patchJson('/api/v1/tasks/batch-status', [
                'ids' => [$task->id],
                'status' => 'done',
            ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/tasks/{$task->id}/activity");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_batch_requires_ids(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson('/api/v1/tasks/batch-status', [
                'status' => 'done',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids']);
    }

    public function test_batch_requires_valid_status(): void
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->patchJson('/api/v1/tasks/batch-status', [
                'ids' => [$task->id],
                'status' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}
