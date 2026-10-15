<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

/** Attendance, grades and task board: instructors manage their own groups only. */
class GroupPolicy
{
    use Concerns;

    public function viewAny(User $user): bool
    {
        return $this->instructor($user);
    }

    public function view(User $user, Group $group): bool
    {
        return $this->instructor($user) && $group->instructor_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Group $group): bool
    {
        return $this->view($user, $group);
    }

    public function delete(User $user, Group $group): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
