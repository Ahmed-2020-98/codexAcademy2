<?php

namespace App\Services\Finance;

use App\Enums\Role;
use App\Enums\SessionStatus;
use App\Models\GroupSession;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Instructor payables = completed session hours × approved hourly rate.
 */
class InstructorPayableCalculator
{
    /**
     * @return Collection<int, array{instructor: User, sessions: int, hours: float, rate: float, amount: float, paid: float, outstanding: float}>
     */
    public function forPeriod(CarbonInterface $from, CarbonInterface $to, ?int $instructorId = null): Collection
    {
        $hours = GroupSession::query()
            ->join('groups', 'groups.id', '=', 'group_sessions.group_id')
            ->where('group_sessions.status', SessionStatus::Completed)
            ->whereBetween('group_sessions.starts_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($instructorId, fn ($q) => $q->where('groups.instructor_id', $instructorId))
            ->whereNotNull('groups.instructor_id')
            ->groupBy('groups.instructor_id')
            ->selectRaw('groups.instructor_id, COUNT(*) as sessions, SUM(group_sessions.duration_hours) as hours')
            ->get()
            ->keyBy('instructor_id');

        return User::role(Role::Instructor->value)
            ->when($instructorId, fn ($q) => $q->whereKey($instructorId))
            ->with('instructorProfile')
            ->withSum(['payouts as paid_sum' => fn ($q) => $q
                ->whereNotNull('paid_at')
                ->whereDate('period_start', '>=', $from->toDateString())
                ->whereDate('period_end', '<=', $to->toDateString())], 'amount')
            ->get()
            ->map(function (User $instructor) use ($hours) {
                $row = $hours->get($instructor->id);
                $rate = (float) ($instructor->instructorProfile?->hourly_rate ?? 0);
                $total = (float) ($row->hours ?? 0);
                $amount = round($total * $rate, 2);
                $paid = (float) ($instructor->paid_sum ?? 0);

                return [
                    'instructor' => $instructor,
                    'sessions' => (int) ($row->sessions ?? 0),
                    'hours' => $total,
                    'rate' => $rate,
                    'amount' => $amount,
                    'paid' => $paid,
                    'outstanding' => max(0, round($amount - $paid, 2)),
                ];
            })
            ->sortByDesc('amount')
            ->values();
    }

    public function totalForPeriod(CarbonInterface $from, CarbonInterface $to): float
    {
        return round($this->forPeriod($from, $to)->sum('amount'), 2);
    }
}
