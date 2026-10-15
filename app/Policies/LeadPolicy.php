<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

/** Pipeline & SLA: Super Admin full, Sales edit & follow-up, others blocked. */
class LeadPolicy
{
    use Concerns;

    public function viewAny(User $user): bool
    {
        return $this->sales($user);
    }

    public function view(User $user, Lead $lead): bool
    {
        return $this->sales($user);
    }

    public function create(User $user): bool
    {
        return $this->sales($user);
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->sales($user);
    }

    /** Dropout reasons: Sales enter them, only Super Admin approves. */
    public function approveDropout(User $user, Lead $lead): bool
    {
        return false;
    }

    public function delete(User $user, Lead $lead): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
