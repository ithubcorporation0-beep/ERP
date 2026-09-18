<?php

namespace App\Http\Controllers;

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Notifications\ProjectMemberAddedNotification;
use App\Policies\ProjectPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Project::class, 'project');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $projects = app(ProjectPolicy::class)
            ->scopeForUser(Project::query(), $request->user())
            ->with('customer')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('customer_id'), fn ($query) => $query->where('customer_id', $request->integer('customer_id')))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('projects.index', [
            'projects' => $projects,
            'statuses' => ProjectStatus::cases(),
            'customers' => Customer::orderBy('name')->get(),
            'status' => $request->string('status')->toString(),
            'customerId' => $request->integer('customer_id') ?: null,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('projects.create', [
            'project' => new Project(),
            'statuses' => ProjectStatus::cases(),
            'customers' => Customer::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = Project::create($request->validated());

        return redirect()->route('projects.show', $project)
            ->with('status', 'Project created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project): View
    {
        $project->load(['customer', 'users', 'tasks']);

        return view('projects.show', [
            'project' => $project,
            'memberRoles' => ProjectMemberRole::cases(),
            'availableUsers' => User::whereDoesntHave('projects', fn ($q) => $q->whereKey($project->id))
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project): View
    {
        return view('projects.edit', [
            'project' => $project,
            'statuses' => ProjectStatus::cases(),
            'customers' => Customer::orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return redirect()->route('projects.show', $project)
            ->with('status', 'Project updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('projects.index')
            ->with('status', 'Project deleted.');
    }

    /**
     * Add a user to the project with a given role.
     */
    public function addMember(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('manageMembers', $project);

        $data = $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('project_members')->where('project_id', $project->id),
            ],
            'role' => ['required', new Enum(ProjectMemberRole::class)],
        ]);

        $project->members()->create($data);

        User::find($data['user_id'])->notify(new ProjectMemberAddedNotification($project, ProjectMemberRole::from($data['role'])));

        return back()->with('status', 'Member added.');
    }

    /**
     * Update a project member's role.
     */
    public function updateMemberRole(Request $request, Project $project, ProjectMember $member): RedirectResponse
    {
        $this->authorize('manageMembers', $project);
        abort_unless($member->project_id === $project->id, 404);

        $data = $request->validate([
            'role' => ['required', new Enum(ProjectMemberRole::class)],
        ]);

        $member->update($data);

        return back()->with('status', 'Member role updated.');
    }

    /**
     * Remove a user from the project.
     */
    public function removeMember(Project $project, ProjectMember $member): RedirectResponse
    {
        $this->authorize('manageMembers', $project);
        abort_unless($member->project_id === $project->id, 404);

        $member->delete();

        return back()->with('status', 'Member removed.');
    }
}
