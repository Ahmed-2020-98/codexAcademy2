<?php

namespace App\Filament\Widgets;

use App\Enums\CourseType;
use App\Models\Payment;
use Filament\Widgets\ChartWidget;

class RevenueTrendChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'الإيرادات حسب المصدر — آخر 6 أشهر';

    protected ?string $maxHeight = '280px';

    public static function canView(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $colors = [CourseType::Recorded->value => '#0A5A9E', CourseType::Live->value => '#33B8F6', CourseType::Offline->value => '#94A3B8'];

        return [
            'labels' => $months->map(fn ($m) => $m->translatedFormat('M'))->all(),
            'datasets' => collect(CourseType::cases())->map(fn (CourseType $type) => [
                'label' => $type->getLabel(),
                'data' => $months->map(fn ($m) => (float) Payment::paid()->where('channel', $type)->whereBetween('paid_at', [$m, $m->copy()->endOfMonth()])->sum('amount'))->all(),
                'backgroundColor' => $colors[$type->value],
                'borderRadius' => 4,
                'stack' => 'rev',
            ])->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return ['scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true]], 'plugins' => ['legend' => ['position' => 'bottom']]];
    }
}
