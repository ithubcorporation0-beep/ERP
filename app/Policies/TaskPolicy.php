<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Builder;

class TaskPolicy extends BasePolicy
{
    /**
     * ADMIN+ and MANAGER see every task. EMPLOYEE sees tasks they're
     * assigned to or that belong to a project they're a member of.
     * CLIENT sees (read-only) tasks under their own customer's projects.
     * Everyone else (e.g. ACCOUNTANT) is denied.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasRoles($user, [
            Roles::SUPER_ADMIN,
            Roles::ADMIN,
            Roles::MANAGER,
            Roles::EMPLOYEE,
            Roles::CLIENT,
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Task $task): bool
    {
        if ($this->hasRoles($user, [Roles::SUPER_ADMIN, Roles::ADMIN, Roles::MANAGER])) {
            return true;
        }

        if ($user->hasRole(Roles::EMPLOYEE)) {
            return $task->assignees()->whereKey($user->id)->exists()
                || $task->project->users()->whereKey($user->id)->exists();
        }

        if ($user->hasRole(Roles::CLIENT)) {
            return $user->customer_id !== null && $user->customer_id === $task->project->customer_id;
        }

        return false;
    }

    /**
     * Only ADMIN+ and MANAGER create tasks.
     */
    public function create(User $user): bool
    {
        return $this->hasRoles($user, [Roles::SUPER_ADMIN, Roles::ADMIN, Roles::MANAGER]);
    }

    /**
     * Full edit (title, description, priority, due date, project, and
     * status) is ADMIN+/MANAGER only. CLIENT can never update.
     */
    public function update(User $user, Task $task): bool
    {
        return $this->create($user);
    }

    /**
     * A narrower ability for the quick "mark done" style status change:
     * ADMIN+/MANAGER (same as update), plus an EMPLOYEE assigned to the
     * task. CLIENT is never allowed.
     */
    public function updateStatus(User $user, Task $task): bool
    {
        if ($this->update($user, $task)) {
            return true;
        }

        return $user->hasRole(Roles::EMPLOYEE) && $task->assignees()->whereKey($user->id)->exists();
    }

    /**
     * Managing assignees follows the same rule as the full update.
     */
    public function manageAssignees(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    /**
     * ADMIN+ and MANAGER only.
     */
    public function delete(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    /**
     * Scope a tasks query to what the given user is allowed to see,
     * matching the view() rules above.
     */
    public function scopeForUser(Builder $query, User $user, string $ownerColumn = 'user_id'): Builder
    {
        if ($this->hasRoles($user, [Roles::SUPER_ADMIN, Roles::ADMIN, Roles::MANAGER])) {
            return $query;
        }

        if ($user->hasRole(Roles::EMPLOYEE)) {
            return $query->where(function (Builder $q) use ($user) {
                $q->whereHas('assignees', fn ($q2) => $q2->whereKey($user->id))
                    ->orWhereHas('project.users', fn ($q2) => $q2->whereKey($user->id));
            });
        }

        if ($user->hasRole(Roles::CLIENT)) {
            return $query->whereHas('project', fn ($q) => $q->where('customer_id', $user->customer_id));
        }

        return $query->whereRaw('1 = 0');
    }
}
