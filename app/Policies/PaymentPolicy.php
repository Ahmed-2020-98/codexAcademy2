<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

/**
 * Finance is restricted for Sales: they can record manual bookings and see
 * the ones they entered; approval and everything else is Super Admin only.
 */
class PaymentPolicy
{
    use Concerns;

    public function viewAny(User $user): bool
    {
        return $this->sales($user);
    }

    public function view(User $user, Payment $payment): bool
    {
        return $this->sales($user) && $payment->recorded_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->sales($user);
    }

    public function update(User $user, Payment $payment): bool
    {
        return $this->sales($user) && $payment->recorded_by === $user->id && $payment->approved_at === null;
    }

    public function approve(User $user, Payment $payment): bool
    {
        return false;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
