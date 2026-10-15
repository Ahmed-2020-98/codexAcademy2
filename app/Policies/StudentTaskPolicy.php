<?php

namespace App\Policies;

use App\Models\StudentTask;
use App\Models\User;

/** Task board: instructors assign/edit for their groups; students only tick completion. */
class StudentTaskPolicy
{
    use Concerns;

    public function viewAny(User $user): bool
    {
        return $this->instructor($user);
    }

    public function view(User $user, StudentTask $task): bool
    {
        return $this->ownsGroup($user, $task) || $task->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->instructor($user);
    }

    public function update(User $user, StudentTask $task): bool
    {
        return $this->ownsGroup($user, $task);
    }

    public function toggle(User $user, StudentTask $task): bool
    {
        return $task->user_id === $user->id;
    }

    public function delete(User $user, StudentTask $task): bool
    {
        return $this->ownsGroup($user, $task);
    }

    public function deleteAny(User $user): bool
    {
        return $this->instructor($user);
    }

    protected function ownsGroup(User $user, StudentTask $task): bool
    {
        return $this->instructor($user) && $task->group?->instructor_id === $user->id;
    }
}
