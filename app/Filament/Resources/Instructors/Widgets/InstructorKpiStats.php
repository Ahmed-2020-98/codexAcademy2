<?php

namespace App\Filament\Resources\Instructors\Widgets;

use App\Services\Finance\InstructorPayableCalculator;
use App\Services\Instructors\InstructorKpis;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class InstructorKpiStats extends StatsOverviewWidget
{
    public ?Model $record = null;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $instructor = $this->record->user;
        $kpi = app(InstructorKpis::class)->for($instructor);
        $month = app(InstructorPayableCalculator::class)->forPeriod(now()->startOfMonth(), now(), $instructor->id)->first();

        $stats = [
            Stat::make('إجمالي الساعات المنفذة', number_format($kpi['hours'], 1))->description($kpi['sessions'].' محاضرة مكتملة')->icon('heroicon-o-clock')->color('primary'),
            Stat::make('أعداد الطلاب', $kpi['students'])->description('في كل كورساته')->icon('heroicon-o-users')->color('info'),
            Stat::make('متوسط تقدم الطلاب', $kpi['avg_progress'].'%')->description('معدل الحضور '.$kpi['attendance_rate'].'%')->icon('heroicon-o-arrow-trending-up')->color('success'),
        ];

        if (auth()->user()->isSuperAdmin() || auth()->id() === $instructor->id) {
            $stats[] = Stat::make('مستحقات هذا الشهر', number_format($month['amount'] ?? 0).' ج.م')
                ->description(number_format($month['hours'] ?? 0, 1).' ساعة × '.number_format($month['rate'] ?? 0).' ج.م')
                ->icon('heroicon-o-banknotes');
            $stats[] = Stat::make('سجل الالتزام', $kpi['absences'])->description('حالات غياب / اعتذار مسجلة')->icon('heroicon-o-calendar')->color($kpi['absences'] > 2 ? 'danger' : 'gray');
            $done = $instructor->upskillingTasks()->where('status', 'done')->count();
            $all = $instructor->upskillingTasks()->count();
            $stats[] = Stat::make('مهام التطوير', "{$done} / {$all}")->description('منجزة من خطة رفع الكفاءة')->icon('heroicon-o-academic-cap')->color('warning');
        }

        return $stats;
    }
}
