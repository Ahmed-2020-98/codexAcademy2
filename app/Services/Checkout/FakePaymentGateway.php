<?php

namespace App\Services\Checkout;

use App\Contracts\PaymentGateway;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Str;

/** Local driver: approves every charge. Swap for Paymob / Moyasar / Tap in production. */
class FakePaymentGateway implements PaymentGateway
{
    public function charge(User $user, Course $course, float $amount): array
    {
        return ['ok' => true, 'reference' => 'TEST-'.Str::upper(Str::random(10)), 'method' => 'card'];
    }
}
