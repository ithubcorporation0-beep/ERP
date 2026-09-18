<?php

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Policies\TaskPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Task::class, 'task');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $tasks = app(TaskPolicy::class)
            ->scopeForUser(Task::query(), $request->user())
            ->with(['project', 'assignees'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('assignee_id'), fn ($query) => $query->whereHas(
                'assignees',
                fn ($q) => $q->whereKey($request->integer('assignee_id'))
            ))
            ->when($request->filled('due_from'), fn ($query) => $query->whereDate('due_date', '>=', $request->date('due_from')))
            ->when($request->filled('due_to'), fn ($query) => $query->whereDate('due_date', '<=', $request->date('due_to')))
            ->orderByRaw("CASE priority WHEN 'URGENT' THEN 0 WHEN 'HIGH' THEN 1 WHEN 'MEDIUM' THEN 2 ELSE 3 END")
            ->orderBy('due_date')
            ->paginate(15)
            ->withQueryString();

        return view('tasks.index', [
            'tasks' => $tasks,
            'statuses' => TaskStatus::cases(),
            'users' => User::orderBy('name')->get(),
            'status' => $request->string('status')->toString(),
            'assigneeId' => $request->integer('assignee_id') ?: null,
            'dueFrom' => $request->string('due_from')->toString(),
            'dueTo' => $request->string('due_to')->toString(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        return view('tasks.create', [
            'task' => new Task(),
            'statuses' => TaskStatus::cases(),
            'priorities' => TaskPriority::cases(),
            'projects' => Project::orderBy('name')->get(),
            'selectedProjectId' => $request->integer('project_id') ?: null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $task = Task::create($request->validated());

        return redirect()->route('tasks.show', $task)
            ->with('status', 'Task created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task): View
    {
        $task->load(['project.customer', 'assignees']);

        return view('tasks.show', [
            'task' => $task,
            'statuses' => TaskStatus::cases(),
            'availableUsers' => User::whereDoesntHave('assignedTasks', fn ($q) => $q->whereKey($task->id))
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Task $task): View
    {
        return view('tasks.edit', [
            'task' => $task,
            'statuses' => TaskStatus::cases(),
            'priorities' => TaskPriority::cases(),
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $task->update($request->validated());

        return redirect()->route('tasks.show', $task)
            ->with('status', 'Task updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return redirect()->route('tasks.index')
            ->with('status', 'Task deleted.');
    }

    /**
     * Quick status change (e.g. "mark done"), open to ADMIN+/MANAGER
     * and an assigned EMPLOYEE, but not a full edit.
     */
    public function updateStatus(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('updateStatus', $task);

        $data = $request->validate([
            'status' => ['required', new Enum(TaskStatus::class)],
        ]);

        $task->update($data);

        return back()->with('status', 'Task status updated.');
    }

    /**
     * Assign a user to the task.
     */
    public function addAssignee(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('manageAssignees', $task);

        $data = $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('task_assignees')->where('task_id', $task->id),
            ],
        ]);

        $task->assignees()->attach($data['user_id']);

        User::find($data['user_id'])->notify(new TaskAssignedNotification($task));

        return back()->with('status', 'Assignee added.');
    }

    /**
     * Remove a user from the task.
     */
    public function removeAssignee(Task $task, User $user): RedirectResponse
    {
        $this->authorize('manageAssignees', $task);

        $task->assignees()->detach($user->id);

        return back()->with('status', 'Assignee removed.');
    }
}
