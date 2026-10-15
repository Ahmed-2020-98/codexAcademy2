<?php

namespace App\Filament\Pages\Widgets;

use App\Services\Finance\ProfitAndLoss;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Reactive;

class ProfitLossChart extends ChartWidget
{
    protected ?string $heading = 'الإيرادات مقابل التكاليف شهرياً';

    protected ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = 'full';

    #[Reactive]
    public ?string $from = null;

    #[Reactive]
    public ?string $to = null;

    protected function getData(): array
    {
        $months = app(ProfitAndLoss::class)->report(Carbon::parse($this->from), Carbon::parse($this->to))['monthly'];

        return [
            'labels' => array_column($months, 'label'),
            'datasets' => [
                ['type' => 'bar', 'label' => 'الإيرادات', 'data' => array_column($months, 'revenue'), 'backgroundColor' => '#0A5A9E', 'borderRadius' => 4],
                ['type' => 'bar', 'label' => 'التكاليف (مصروفات + مستحقات)', 'data' => array_column($months, 'costs'), 'backgroundColor' => '#33B8F6', 'borderRadius' => 4],
                ['type' => 'line', 'label' => 'صافي الربح', 'data' => array_column($months, 'net'), 'borderColor' => '#16A34A', 'backgroundColor' => '#16A34A', 'tension' => 0.3, 'pointRadius' => 4],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
