<?php

namespace App\Policies;

use App\Models\InstructorAttendanceLog;
use App\Models\User;

class InstructorAttendanceLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isInstructor();
    }

    public function view(User $user, InstructorAttendanceLog $log): bool
    {
        return $log->instructor_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, InstructorAttendanceLog $log): bool
    {
        return false;
    }

    public function delete(User $user, InstructorAttendanceLog $log): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
