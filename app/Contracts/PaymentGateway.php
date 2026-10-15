<?php

namespace App\Contracts;

use App\Models\Course;
use App\Models\User;

interface PaymentGateway
{
    /**
     * Charge the student and return the provider reference.
     *
     * @return array{ok: bool, reference: string, method: string}
     */
    public function charge(User $user, Course $course, float $amount): array;
}
