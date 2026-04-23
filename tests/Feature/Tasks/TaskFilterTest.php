<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'member']);
    }

    public function test_can_filter_by_status(): void
    {
        Task::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);
        Task::factory()->create(['user_id' => $this->user->id, 'status' => 'done']);
        Task::factory()->create(['user_id' => $this->user->id, 'status' => 'done']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tasks?status=done');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_can_filter_by_priority(): void
    {
        Task::factory()->create(['user_id' => $this->user->id, 'priority' => 'high']);
        Task::factory()->create(['user_id' => $this->user->id, 'priority' => 'low']);
        Task::factory()->create(['user_id' => $this->user->id, 'priority' => 'low']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tasks?priority=low');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_can_filter_by_assigned_to_me(): void
    {
        Task::factory()->create(['user_id' => $this->user->id, 'assigned_to' => $this->user->id]);
        Task::factory()->create(['user_id' => $this->user->id, 'assigned_to' => null]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tasks?assigned_to=me');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_can_search_by_title(): void
    {
        Task::factory()->create(['user_id' => $this->user->id, 'title' => 'Fix login bug']);
        Task::factory()->create(['user_id' => $this->user->id, 'title' => 'Add new feature']);
        Task::factory()->create(['user_id' => $this->user->id, 'title' => 'Fix registration bug']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tasks?search=bug');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_can_search_by_description(): void
    {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Task 1',
            'description' => 'This is urgent task',
        ]);
        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Task 2',
            'description' => 'Normal task',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tasks?search=urgent');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_can_sort_by_created_at_desc(): void
    {
        $task1 = Task::factory()->create(['user_id' => $this->user->id, 'created_at' => now()->subDay()]);
        $task2 = Task::factory()->create(['user_id' => $this->user->id, 'created_at' => now()]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tasks?sort=-created_at');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals($task2->id, $data[0]['id']);
    }

    public function test_can_sort_by_priority_asc(): void
    {
        Task::factory()->create(['user_id' => $this->user->id, 'priority' => 'high']);
        Task::factory()->create(['user_id' => $this->user->id, 'priority' => 'low']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tasks?sort=priority');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals('high', $data[0]['priority']);
    }

    public function test_can_combine_filters(): void
    {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'done',
            'priority' => 'high',
            'title' => 'Important task',
        ]);
        Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
            'priority' => 'high',
            'title' => 'Another task',
        ]);
        Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'done',
            'priority' => 'low',
            'title' => 'Low priority done',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tasks?status=done&priority=high&search=Important');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1);
    }
}
