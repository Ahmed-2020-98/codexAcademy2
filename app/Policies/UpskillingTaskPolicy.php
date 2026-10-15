<?php

namespace App\Policies;

use App\Models\UpskillingTask;
use App\Models\User;

/** Up-skilling plans are assigned by management; instructors update their own progress. */
class UpskillingTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isInstructor();
    }

    public function view(User $user, UpskillingTask $task): bool
    {
        return $task->instructor_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, UpskillingTask $task): bool
    {
        return $task->instructor_id === $user->id;
    }

    public function delete(User $user, UpskillingTask $task): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
