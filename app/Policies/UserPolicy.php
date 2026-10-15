<?php

namespace App\Policies;

use App\Models\User;

/** Student & staff accounts: Super Admin manages; Sales can look students up. */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSales();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isSales() && $model->isStudent();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, User $model): bool
    {
        return false;
    }

    public function delete(User $user, User $model): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
