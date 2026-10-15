<?php

namespace App\Filament\Widgets;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Filament\Widgets\ChartWidget;

class PipelineChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'توزيع الطلاب على مراحل الـ Pipeline';

    protected ?string $maxHeight = '280px';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->isSuperAdmin() || $user?->isSales();
    }

    protected function getData(): array
    {
        $counts = Lead::query()
            ->when(auth()->user()->isSales(), fn ($q) => $q->where('assigned_to', auth()->id()))
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        return [
            'labels' => collect(LeadStatus::cases())->map->getLabel()->all(),
            'datasets' => [[
                'data' => collect(LeadStatus::cases())->map(fn ($s) => (int) ($counts[$s->value] ?? 0))->all(),
                'backgroundColor' => collect(LeadStatus::cases())->map->hex()->all(),
                'borderWidth' => 0,
            ]],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return ['cutout' => '62%', 'plugins' => ['legend' => ['position' => 'bottom']], 'scales' => ['x' => ['display' => false], 'y' => ['display' => false]]];
    }
}
