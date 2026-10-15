<?php

namespace App\Policies;

use App\Models\ReferralCode;
use App\Models\User;

/** Sales can follow referral performance; changes are Super Admin only. */
class ReferralCodePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSales();
    }

    public function view(User $user, ReferralCode $code): bool
    {
        return $user->isSales();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ReferralCode $code): bool
    {
        return false;
    }

    public function delete(User $user, ReferralCode $code): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
