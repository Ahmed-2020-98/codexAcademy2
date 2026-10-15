<?php

namespace App\Services\Checkout;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\ReferralCode;
use App\Models\User;
use App\Services\WhatsApp\WhatsApp;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(private PaymentGateway $gateway, private WhatsApp $whatsApp) {}

    /** @return array{gross: float, discount: float, total: float, code: ?ReferralCode} */
    public function quote(User $user, Course $course, ?string $code = null): array
    {
        $gross = $course->effectivePrice();
        $referral = $this->resolveCode($user, $code);
        $discount = $referral ? round($gross * $referral->discount_percent / 100, 2) : 0.0;

        return ['gross' => $gross, 'discount' => $discount, 'total' => round($gross - $discount, 2), 'code' => $referral];
    }

    public function purchase(User $user, Course $course, ?string $code = null): Enrollment
    {
        if ($user->isEnrolledIn($course)) {
            return $user->enrollments()->where('course_id', $course->id)->first();
        }

        $quote = $this->quote($user, $course, $code);
        $charge = $this->gateway->charge($user, $course, $quote['total']);

        if (! $charge['ok']) {
            throw ValidationException::withMessages(['payment' => 'تعذر إتمام عملية الدفع، حاول مرة أخرى.']);
        }

        $enrollment = DB::transaction(function () use ($user, $course, $quote, $charge) {
            $payment = Payment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'channel' => $course->type,
                'source' => 'online',
                'method' => $charge['method'],
                'gross_amount' => $quote['gross'],
                'discount_amount' => $quote['discount'],
                'amount' => $quote['total'],
                'referral_code_id' => $quote['code']?->id,
                'reference' => $charge['reference'],
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
            ]);

            if ($quote['code']) {
                $quote['code']->redemptions()->create([
                    'user_id' => $user->id,
                    'payment_id' => $payment->id,
                    'discount_amount' => $quote['discount'],
                ]);
                $quote['code']->increment('uses_count');
            }

            ReferralCode::issueFor($user);

            return $user->enrollments()->create([
                'course_id' => $course->id,
                'status' => 'active',
                'enrolled_at' => now(),
            ]);
        });

        $this->whatsApp->send($user->phone, "تم تفعيل اشتراكك في «{$course->title}» بنجاح 🎉 ابدأ التعلم الآن من لوحة الطالب.", 'course.purchased', $enrollment);

        return $enrollment;
    }

    protected function resolveCode(User $user, ?string $code): ?ReferralCode
    {
        if (blank($code)) {
            return null;
        }

        $referral = ReferralCode::query()->where('code', strtoupper(trim($code)))->where('is_active', true)->first();

        if (! $referral || $referral->user_id === $user->id) {
            throw ValidationException::withMessages(['referral_code' => 'كود الإحالة غير صالح.']);
        }

        return $referral;
    }
}
