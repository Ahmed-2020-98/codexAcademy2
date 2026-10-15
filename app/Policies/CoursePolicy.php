<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

/** Content: Super Admin full, instructors their own courses only. */
class CoursePolicy
{
    use Concerns;

    public function viewAny(User $user): bool
    {
        return $this->instructor($user);
    }

    public function view(User $user, Course $course): bool
    {
        return $this->instructor($user) && $course->instructor_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Course $course): bool
    {
        return $this->view($user, $course);
    }

    public function delete(User $user, Course $course): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
