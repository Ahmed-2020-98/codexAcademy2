<?php

namespace App\Filament\Pages;

use App\Filament\Support\NavGroup;
use App\Models\Payment;
use App\Services\Finance\ProfitAndLoss;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Net Profit = Gross Revenue − (Operating Expenses + Instructor Payables) */
class ProfitLoss extends Page
{
    protected string $view = 'filament.pages.profit-loss';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static string|\UnitEnum|null $navigationGroup = NavGroup::FINANCE;

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'تقرير الأرباح والخسائر';

    protected static ?string $title = 'تقرير الأرباح والخسائر (P&L)';

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $to = null;

    /** P&L is Super Admin only; Sales have restricted finance access. */
    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->from ??= now()->startOfMonth()->subMonths(5)->toDateString();
        $this->to ??= now()->toDateString();
    }

    public function preset(string $preset): void
    {
        [$from, $to] = match ($preset) {
            'month' => [now()->startOfMonth(), now()],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'quarter' => [now()->firstOfQuarter(), now()],
            'year' => [now()->startOfYear(), now()],
            default => [now()->startOfMonth()->subMonths(5), now()],
        };

        $this->from = $from->toDateString();
        $this->to = $to->toDateString();
    }

    public function getReport(): array
    {
        return app(ProfitAndLoss::class)->report(Carbon::parse($this->from), Carbon::parse($this->to));
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('print')->label('طباعة / حفظ PDF')->icon('heroicon-o-printer')
                    ->url(fn () => route('admin.reports.pl.print', ['from' => $this->from, 'to' => $this->to]), shouldOpenInNewTab: true),
                Action::make('csv')->label('تصدير CSV')->icon('heroicon-o-table-cells')->action(fn () => $this->exportCsv()),
            ])->label('استخراج التقرير')->icon('heroicon-o-arrow-down-tray')->button()->color('primary'),
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $report = $this->getReport();

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['تقرير الأرباح والخسائر', $report['from']->toDateString().' → '.$report['to']->toDateString()]);
            fputcsv($out, []);
            fputcsv($out, ['البند', 'القيمة']);
            foreach ($report['revenue_by_channel'] as $row) {
                fputcsv($out, ['إيرادات — '.$row['label'], $row['total']]);
            }
            fputcsv($out, ['إجمالي الإيرادات', $report['gross_revenue']]);
            foreach ($report['expenses_by_category'] as $row) {
                fputcsv($out, ['مصروف — '.$row['label'], $row['total']]);
            }
            fputcsv($out, ['إجمالي المصروفات التشغيلية', $report['operating_expenses']]);
            fputcsv($out, ['مستحقات المحاضرين', $report['instructor_payables']]);
            fputcsv($out, ['صافي الربح', $report['net_profit']]);
            fputcsv($out, ['هامش الربح %', $report['margin']]);
            fclose($out);
        }, 'pl-'.$this->from.'-'.$this->to.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pendingManualBookings(): int
    {
        return Payment::where('status', 'pending')->count();
    }
}
