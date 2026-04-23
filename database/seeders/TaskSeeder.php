<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $admin = $users->where('role', 'admin')->first();
        $members = $users->where('role', 'member');

        $tasks = [
            ['title' => 'Setup CI/CD pipeline', 'description' => 'Configure GitHub Actions for automated testing and deployment', 'status' => 'done', 'priority' => 'high', 'tags' => ['devops', 'ci']],
            ['title' => 'Design database schema', 'description' => 'Create ERD and implement migrations', 'status' => 'done', 'priority' => 'high', 'tags' => ['backend', 'database']],
            ['title' => 'Implement authentication', 'description' => 'Add Sanctum token-based auth with register/login/logout', 'status' => 'done', 'priority' => 'high', 'tags' => ['backend', 'auth']],
            ['title' => 'Create Task CRUD API', 'description' => 'Build RESTful endpoints for task management', 'status' => 'done', 'priority' => 'high', 'tags' => ['backend', 'api']],
            ['title' => 'Add role-based access control', 'description' => 'Implement admin/member roles with policies', 'status' => 'in_progress', 'priority' => 'high', 'tags' => ['backend', 'auth']],
            ['title' => 'Write API documentation', 'description' => 'Add Swagger/OpenAPI annotations to all endpoints', 'status' => 'in_progress', 'priority' => 'medium', 'tags' => ['docs']],
            ['title' => 'Implement batch operations', 'description' => 'Add batch status update endpoint', 'status' => 'pending', 'priority' => 'medium', 'tags' => ['backend', 'api']],
            ['title' => 'Add activity logging', 'description' => 'Track all task changes with audit trail', 'status' => 'pending', 'priority' => 'medium', 'tags' => ['backend', 'logging']],
            ['title' => 'Setup Docker environment', 'description' => 'Create Dockerfile and docker-compose for local development', 'status' => 'done', 'priority' => 'medium', 'tags' => ['devops', 'docker']],
            ['title' => 'Add rate limiting', 'description' => 'Implement rate limiting on auth endpoints', 'status' => 'pending', 'priority' => 'low', 'tags' => ['backend', 'security']],
            ['title' => 'Create Bruno collection', 'description' => 'Build API collection for testing', 'status' => 'done', 'priority' => 'low', 'tags' => ['testing', 'docs']],
            ['title' => 'Optimize queries', 'description' => 'Add eager loading and indexes for performance', 'status' => 'pending', 'priority' => 'medium', 'tags' => ['backend', 'performance']],
            ['title' => 'Add pagination metadata', 'description' => 'Include total, per_page, current_page in responses', 'status' => 'done', 'priority' => 'low', 'tags' => ['backend', 'api']],
            ['title' => 'Implement soft deletes', 'description' => 'Add soft delete functionality to tasks', 'status' => 'done', 'priority' => 'low', 'tags' => ['backend']],
            ['title' => 'Add health check endpoint', 'description' => 'Create /api/v1/health endpoint', 'status' => 'pending', 'priority' => 'low', 'tags' => ['backend', 'devops']],
            ['title' => 'Setup testing framework', 'description' => 'Configure PHPUnit with SQLite in-memory', 'status' => 'done', 'priority' => 'high', 'tags' => ['testing']],
            ['title' => 'Write auth tests', 'description' => 'Test register, login, logout, me endpoints', 'status' => 'done', 'priority' => 'high', 'tags' => ['testing', 'auth']],
            ['title' => 'Write CRUD tests', 'description' => 'Test all task CRUD operations', 'status' => 'done', 'priority' => 'high', 'tags' => ['testing', 'api']],
            ['title' => 'Write filter tests', 'description' => 'Test filtering, sorting, search functionality', 'status' => 'pending', 'priority' => 'medium', 'tags' => ['testing', 'api']],
            ['title' => 'Write authorization tests', 'description' => 'Test role-based access control', 'status' => 'pending', 'priority' => 'medium', 'tags' => ['testing', 'auth']],
            ['title' => 'Add API versioning', 'description' => 'Prefix all routes with /api/v1/', 'status' => 'done', 'priority' => 'low', 'tags' => ['backend', 'api']],
            ['title' => 'Create README', 'description' => 'Write project documentation with examples', 'status' => 'pending', 'priority' => 'medium', 'tags' => ['docs']],
            ['title' => 'Review code quality', 'description' => 'Run linter and fix code style issues', 'status' => 'pending', 'priority' => 'low', 'tags' => ['quality']],
            ['title' => 'Deploy to staging', 'description' => 'Setup staging environment and deploy', 'status' => 'pending', 'priority' => 'low', 'tags' => ['devops']],
        ];

        foreach ($tasks as $index => $taskData) {
            $assignedUser = $index % 3 === 0 ? null : $members->random();
            
            $task = Task::create([
                ...$taskData,
                'user_id' => $admin->id,
                'assigned_to' => $assignedUser?->id,
                'due_date' => now()->addDays(rand(1, 30)),
                'created_at' => now()->subDays(count($tasks) - $index),
            ]);

            ActivityLog::create([
                'task_id' => $task->id,
                'user_id' => $admin->id,
                'action' => 'created',
                'new_values' => $task->toArray(),
            ]);

            if ($task->status === 'done') {
                ActivityLog::create([
                    'task_id' => $task->id,
                    'user_id' => $admin->id,
                    'action' => 'status_changed',
                    'old_values' => ['status' => 'pending'],
                    'new_values' => ['status' => 'done'],
                ]);
            }
        }
    }
}
