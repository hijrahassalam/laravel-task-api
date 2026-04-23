<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService
    ) {}

    #[OA\Get(
        path: '/api/v1/tasks',
        summary: 'List all tasks',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['pending', 'in_progress', 'done'])),
            new OA\Parameter(name: 'priority', in: 'query', schema: new OA\Schema(type: 'string', enum: ['low', 'medium', 'high'])),
            new OA\Parameter(name: 'assigned_to', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of tasks'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $tasks = $this->taskService->list($request->user(), $request->only([
            'status', 'priority', 'assigned_to', 'search', 'sort',
        ]));

        return response()->json([
            'data' => TaskResource::collection($tasks),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/tasks',
        summary: 'Create a new task',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Complete project'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Finish the API documentation'),
                    new OA\Property(property: 'status', type: 'string', enum: ['pending', 'in_progress', 'done'], example: 'pending'),
                    new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high'], example: 'medium'),
                    new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true, example: '2026-12-31'),
                    new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'string'), example: ['urgent', 'backend']),
                    new OA\Property(property: 'assigned_to', type: 'integer', nullable: true, example: 2),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Task created successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->taskService->create($request->validated(), $request->user());

        return response()->json([
            'data' => new TaskResource($task),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/tasks/{id}',
        summary: 'Get a single task',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Task details'),
            new OA\Response(response: 404, description: 'Task not found'),
        ]
    )]
    public function show(Task $task): JsonResponse
    {
        $task->load(['user', 'assignee']);

        return response()->json([
            'data' => new TaskResource($task),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/tasks/{id}',
        summary: 'Update a task',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'status', type: 'string', enum: ['pending', 'in_progress', 'done']),
                    new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high']),
                    new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true),
                    new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'string')),
                    new OA\Property(property: 'assigned_to', type: 'integer', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Task updated successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $task = $this->taskService->update($task, $request->validated(), $request->user());

        return response()->json([
            'data' => new TaskResource($task),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/tasks/{id}',
        summary: 'Delete a task (soft delete)',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Task deleted successfully'),
            new OA\Response(response: 404, description: 'Task not found'),
        ]
    )]
    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->taskService->delete($task, $request->user());

        return response()->json([
            'message' => 'Task deleted successfully.',
        ]);
    }
}
