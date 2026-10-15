<?php

namespace App\Policies;

use App\Models\User;

/** Settings / finance area: Super Admin only (handled by Gate::before). */
class InstructorPayoutPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user): bool
    {
        return false;
    }

    public function delete(User $user): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
