<?php

namespace App\Policies;

use App\Models\User;

/**
 * Helpers shared by the policies. Super Admin is granted everything through
 * Gate::before, so policies only describe the other roles.
 */
trait Concerns
{
    protected function sales(User $user): bool
    {
        return $user->isSales();
    }

    protected function instructor(User $user): bool
    {
        return $user->isInstructor();
    }
}
