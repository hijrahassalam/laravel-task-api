<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->member = User::factory()->create(['role' => 'member']);
    }

    public function test_admin_can_see_all_tasks(): void
    {
        Task::factory()->count(3)->create(['user_id' => $this->member->id]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/tasks');

        $response->assertOk()
            ->assertJsonPath('meta.total', 3);
    }

    public function test_member_can_only_see_own_tasks(): void
    {
        Task::factory()->count(2)->create(['user_id' => $this->member->id]);
        Task::factory()->count(3)->create(['user_id' => $this->admin->id]);

        $response = $this->actingAs($this->member)
            ->getJson('/api/v1/tasks');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_member_can_see_assigned_tasks(): void
    {
        Task::factory()->create(['user_id' => $this->admin->id, 'assigned_to' => $this->member->id]);
        Task::factory()->create(['user_id' => $this->admin->id, 'assigned_to' => null]);

        $response = $this->actingAs($this->member)
            ->getJson('/api/v1/tasks');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_member_cannot_view_other_users_task(): void
    {
        $task = Task::factory()->create(['user_id' => $this->admin->id]);

        $response = $this->actingAs($this->member)
            ->getJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_member_cannot_update_other_users_task(): void
    {
        $task = Task::factory()->create(['user_id' => $this->admin->id]);

        $response = $this->actingAs($this->member)
            ->putJson("/api/v1/tasks/{$task->id}", ['title' => 'Hacked']);

        $response->assertStatus(403);
    }

    public function test_member_cannot_delete_other_users_task(): void
    {
        $task = Task::factory()->create(['user_id' => $this->admin->id]);

        $response = $this->actingAs($this->member)
            ->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_assign_task(): void
    {
        $task = Task::factory()->create(['user_id' => $this->admin->id]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/tasks/{$task->id}/assign", [
                'user_id' => $this->member->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.assignee.id', $this->member->id);
    }

    public function test_member_cannot_assign_task(): void
    {
        $task = Task::factory()->create(['user_id' => $this->member->id]);

        $response = $this->actingAs($this->member)
            ->postJson("/api/v1/tasks/{$task->id}/assign", [
                'user_id' => $this->admin->id,
            ]);

        $response->assertStatus(403);
    }
}
