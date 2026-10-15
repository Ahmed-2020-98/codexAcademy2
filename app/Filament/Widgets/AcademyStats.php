<?php

namespace App\Filament\Widgets;

use App\Enums\LeadStatus;
use App\Enums\Role;
use App\Enums\SessionStatus;
use App\Models\GroupSession;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\User;
use App\Services\Finance\InstructorPayableCalculator;
use App\Services\Finance\ProfitAndLoss;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** One stats row per role: admin sees the business, sales their pipeline, instructors their teaching. */
class AcademyStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $user = auth()->user();

        return match (true) {
            $user->isSuperAdmin() => $this->adminStats(),
            $user->isSales() => $this->salesStats($user),
            default => $this->instructorStats($user),
        };
    }

    protected function adminStats(): array
    {
        $thisMonth = (float) Payment::paid()->whereBetween('paid_at', [now()->startOfMonth(), now()])->sum('amount');
        $lastMonth = (float) Payment::paid()->whereBetween('paid_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()])->sum('amount');
        $delta = $lastMonth > 0 ? round(($thisMonth - $lastMonth) / $lastMonth * 100) : 0;
        $pl = app(ProfitAndLoss::class)->report(now()->startOfMonth(), now());

        $daily = collect(range(13, 0))->map(fn ($d) => (float) Payment::paid()->whereDate('paid_at', now()->subDays($d))->sum('amount'))->all();
        $leads = collect(range(13, 0))->map(fn ($d) => Lead::whereDate('created_at', now()->subDays($d))->count())->all();

        return [
            Stat::make('إيرادات الشهر', static::money($thisMonth))
                ->description(($delta >= 0 ? '+' : '').$delta.'% عن نفس الفترة الشهر الماضي')
                ->descriptionIcon($delta >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($delta >= 0 ? 'success' : 'danger')
                ->chart($daily),
            Stat::make('صافي ربح الشهر', static::money($pl['net_profit']))
                ->description('هامش '.$pl['margin'].'% بعد المصروفات والمستحقات')
                ->color($pl['net_profit'] >= 0 ? 'primary' : 'danger')
                ->url(route('filament.admin.pages.profit-loss')),
            Stat::make('تسجيلات جديدة (7 أيام)', Lead::where('created_at', '>=', now()->subDays(7))->count())
                ->description(Lead::where('status', LeadStatus::Pending)->count().' بانتظار التواصل')
                ->color('info')
                ->chart($leads),
            Stat::make('متأخرون عن الـ SLA', Lead::overdue()->count())
                ->description('تجاوزوا 24 ساعة دون تواصل')
                ->descriptionIcon('heroicon-m-bell-alert')
                ->color(Lead::overdue()->exists() ? 'danger' : 'success')
                ->url(route('filament.admin.resources.leads.index', ['activeTab' => 'overdue'])),
        ];
    }

    /** Left-to-right isolate keeps the minus sign of negative amounts on the correct side in RTL. */
    public static function money(float $value): string
    {
        return "\u{2066}".number_format($value)."\u{2069} ج.م";
    }

    protected function salesStats(User $user): array
    {
        $mine = Lead::where('assigned_to', $user->id);
        $monthEnrolled = (clone $mine)->where('status', LeadStatus::Enrolled)->where('updated_at', '>=', now()->startOfMonth())->count();
        $closed = (clone $mine)->whereIn('status', [LeadStatus::Enrolled, LeadStatus::DroppedOut])->count();
        $won = (clone $mine)->where('status', LeadStatus::Enrolled)->count();

        return [
            Stat::make('طلابي قيد الانتظار', (clone $mine)->where('status', LeadStatus::Pending)->count())->color('warning')->descriptionIcon('heroicon-m-clock')->description('بحاجة لأول تواصل'),
            Stat::make('متأخرون (SLA)', (clone $mine)->overdue()->count())->color('danger')->description('الأولوية القصوى')->url(route('filament.admin.resources.leads.index', ['activeTab' => 'overdue'])),
            Stat::make('اختبارات مستوى قادمة', (clone $mine)->where('status', LeadStatus::AssessmentScheduled)->where('assessment_at', '>=', now())->count())->color('info'),
            Stat::make('تسجيلات هذا الشهر', $monthEnrolled)->description('معدل التحويل '.($closed ? round($won / $closed * 100) : 0).'%')->color('success'),
        ];
    }

    protected function instructorStats(User $user): array
    {
        $sessions = GroupSession::whereHas('group', fn ($q) => $q->where('instructor_id', $user->id));
        $month = app(InstructorPayableCalculator::class)->forPeriod(now()->startOfMonth(), now(), $user->id)->first();

        return [
            Stat::make('مجموعاتي', $user->taughtGroups()->count())->color('primary')->description($user->taughtGroups()->withCount('students')->get()->sum('students_count').' طالب'),
            Stat::make('محاضرات هذا الأسبوع', (clone $sessions)->where('status', SessionStatus::Scheduled)->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])->count())->color('info'),
            Stat::make('ساعات هذا الشهر', number_format($month['hours'] ?? 0, 1))->description(number_format($month['amount'] ?? 0).' ج.م مستحقات')->color('success'),
            Stat::make('مهام التطوير المفتوحة', $user->upskillingTasks()->where('status', '!=', 'done')->count())->color('warning'),
        ];
    }
}
