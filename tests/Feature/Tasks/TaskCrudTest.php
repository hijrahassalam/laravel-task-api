<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'member']);
    }

    public function test_user_can_list_tasks(): void
    {
        Task::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tasks');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'status', 'priority'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_user_can_create_task(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/tasks', [
                'title' => 'Test Task',
                'description' => 'Test description',
                'priority' => 'high',
                'tags' => ['urgent', 'backend'],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'title', 'description', 'status', 'priority', 'tags'],
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_show_task(): void
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/tasks/{$task->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $task->id);
    }

    public function test_user_can_update_task(): void
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/tasks/{$task->id}", [
                'title' => 'Updated Task',
                'priority' => 'low',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Task');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Task',
        ]);
    }

    public function test_user_can_delete_task(): void
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertOk()
            ->assertJson(['message' => 'Task deleted successfully.']);

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_user_cannot_view_other_users_task(): void
    {
        $otherUser = User::factory()->create(['role' => 'member']);
        $task = Task::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_task_creation_requires_title(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/tasks', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }
}
