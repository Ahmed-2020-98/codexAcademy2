<?php

namespace App\Policies;

use App\Models\InstructorProfile;
use App\Models\User;

/** Profile & demo videos: Super Admin full, an instructor edits only their own profile. */
class InstructorProfilePolicy
{
    use Concerns;

    public function viewAny(User $user): bool
    {
        return $this->instructor($user);
    }

    public function view(User $user, InstructorProfile $profile): bool
    {
        return $profile->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, InstructorProfile $profile): bool
    {
        return $profile->user_id === $user->id;
    }

    public function delete(User $user, InstructorProfile $profile): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
