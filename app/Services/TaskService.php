<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TaskService
{
    public function list(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Task::with(['user', 'assignee']);

        if ($user->role !== 'admin') {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('assigned_to', $user->id);
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['assigned_to'])) {
            $assignedTo = $filters['assigned_to'] === 'me' ? $user->id : $filters['assigned_to'];
            $query->where('assigned_to', $assignedTo);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['sort'])) {
            $sorts = explode(',', $filters['sort']);
            foreach ($sorts as $sort) {
                $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
                $field = ltrim($sort, '-');
                $query->orderBy($field, $direction);
            }
        } else {
            $query->latest();
        }

        return $query->paginate(15);
    }

    public function create(array $data, User $user): Task
    {
        $task = Task::create([
            ...$data,
            'user_id' => $user->id,
        ]);

        ActivityLog::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'action' => 'created',
            'new_values' => $task->toArray(),
        ]);

        return $task->load(['user', 'assignee']);
    }

    public function update(Task $task, array $data, User $user): Task
    {
        $oldValues = $task->toArray();

        $task->update($data);

        ActivityLog::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'action' => 'updated',
            'old_values' => $oldValues,
            'new_values' => $task->fresh()->toArray(),
        ]);

        return $task->load(['user', 'assignee']);
    }

    public function delete(Task $task, User $user): void
    {
        ActivityLog::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'action' => 'deleted',
            'old_values' => $task->toArray(),
        ]);

        $task->delete();
    }

    public function updateStatus(Task $task, string $status, User $user): Task
    {
        $oldStatus = $task->status;

        $task->update(['status' => $status]);

        ActivityLog::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'action' => 'status_changed',
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => $status],
        ]);

        return $task->load(['user', 'assignee']);
    }
}
