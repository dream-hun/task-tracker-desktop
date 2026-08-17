<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\SummarizeTasks;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    /**
     * Show the user's task list.
     */
    public function index(Request $request, SummarizeTasks $summarize): Response
    {
        $user = $request->user();

        $search = $request->string('search')->trim()->toString() ?: null;
        $status = TaskStatus::tryFrom($request->string('status')->toString());
        $priority = TaskPriority::tryFrom($request->string('priority')->toString());

        $tasks = Task::whereBelongsTo($user)
            ->when($search, fn (Builder $query, string $search) => $query->search($search))
            ->when($status, fn (Builder $query, TaskStatus $status) => $query->withStatus($status))
            ->when($priority, fn (Builder $query, TaskPriority $priority) => $query->withPriority($priority))
            ->orderByUrgency()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('tasks/index', [
            'tasks' => $tasks,
            'stats' => $summarize->handle($user),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'priority' => $priority,
            ],
            'statuses' => TaskStatus::values(),
            'priorities' => TaskPriority::values(),
        ]);
    }

    /**
     * Store a newly created task.
     */
    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $request->user()->tasks()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task created.')]);

        return back();
    }

    /**
     * Update the given task.
     */
    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        Gate::authorize('update', $task);

        $task->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task updated.')]);

        return back();
    }

    /**
     * Delete the given task.
     */
    public function destroy(Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $task->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task deleted.')]);

        return back();
    }
}
