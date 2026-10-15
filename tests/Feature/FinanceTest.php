<?php

namespace Tests\Feature;

use App\Enums\CourseType;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Enums\SessionStatus;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Group;
use App\Models\InstructorPayout;
use App\Models\Payment;
use App\Services\Finance\InstructorPayableCalculator;
use App\Services\Finance\ProfitAndLoss;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_profit_and_loss_follows_the_required_formula(): void
    {
        $this->seedRoles();
        $this->travelTo(now()->setDate(2026, 9, 15));

        $instructor = $this->userWithRole(Role::Instructor);
        $instructor->instructorProfile()->create(['slug' => 'ins', 'hourly_rate' => 300]);
        $course = $this->makeCourse(CourseType::Live, ['instructor_id' => $instructor->id]);
        $group = Group::create(['course_id' => $course->id, 'instructor_id' => $instructor->id, 'name' => 'G', 'mode' => CourseType::Live]);

        // 3 completed sessions × 2h = 6h × 300 = 1,800 payables. Cancelled/scheduled ones don't count.
        foreach ([SessionStatus::Completed, SessionStatus::Completed, SessionStatus::Completed, SessionStatus::Cancelled, SessionStatus::Scheduled] as $i => $status) {
            $group->sessions()->create(['title' => "S{$i}", 'starts_at' => now()->subDays($i + 1), 'duration_hours' => 2, 'status' => $status]);
        }

        foreach ([[CourseType::Recorded, 5000], [CourseType::Live, 3000], [CourseType::Offline, 2000]] as [$channel, $amount]) {
            Payment::create(['channel' => $channel, 'gross_amount' => $amount, 'amount' => $amount, 'status' => PaymentStatus::Paid, 'paid_at' => now()->subDays(2)]);
        }
        Payment::create(['channel' => CourseType::Live, 'gross_amount' => 999, 'amount' => 999, 'status' => PaymentStatus::Pending, 'paid_at' => now()]);
        Payment::create(['channel' => CourseType::Live, 'gross_amount' => 777, 'amount' => 777, 'status' => PaymentStatus::Paid, 'paid_at' => now()->subMonths(3)]);

        $rent = ExpenseCategory::create(['name' => 'إيجار']);
        Expense::create(['expense_category_id' => $rent->id, 'title' => 'Rent', 'amount' => 2500, 'spent_on' => now()->subDays(3)]);
        Expense::create(['expense_category_id' => $rent->id, 'title' => 'Ads', 'amount' => 700, 'spent_on' => now()->subDays(1)]);

        $report = app(ProfitAndLoss::class)->report(now()->startOfMonth(), now());

        $this->assertSame(10000.0, $report['gross_revenue']);
        $this->assertSame(3200.0, $report['operating_expenses']);
        $this->assertSame(1800.0, $report['instructor_payables']);
        $this->assertSame(10000.0 - (3200.0 + 1800.0), $report['net_profit']);
        $this->assertSame(5000.0, $report['revenue_by_channel']['recorded']['total']);
        $this->assertSame(50.0, $report['margin']);
    }

    public function test_payable_outstanding_subtracts_recorded_payouts(): void
    {
        $this->seedRoles();
        $instructor = $this->userWithRole(Role::Instructor);
        $instructor->instructorProfile()->create(['slug' => 'ins2', 'hourly_rate' => 250]);
        $course = $this->makeCourse(CourseType::Offline, ['instructor_id' => $instructor->id]);
        $group = Group::create(['course_id' => $course->id, 'instructor_id' => $instructor->id, 'name' => 'G', 'mode' => CourseType::Offline]);
        $group->sessions()->create(['title' => 'S', 'starts_at' => now()->startOfMonth()->addDay(), 'duration_hours' => 4, 'status' => SessionStatus::Completed]);

        $from = now()->startOfMonth();
        $to = now()->endOfMonth();
        InstructorPayout::create(['instructor_id' => $instructor->id, 'period_start' => $from, 'period_end' => $to, 'hours' => 2, 'amount' => 400, 'paid_at' => now()]);

        $row = app(InstructorPayableCalculator::class)->forPeriod($from, $to, $instructor->id)->sole();

        $this->assertSame(4.0, $row['hours']);
        $this->assertSame(1000.0, $row['amount']);
        $this->assertSame(600.0, $row['outstanding']);
    }
}
