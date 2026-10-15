<?php

namespace App\Filament\Pages;

use App\Enums\LeadStatus;
use App\Filament\Support\NavGroup;
use App\Models\Course;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Drop-out analysis for retention & re-activation campaigns. */
class DropoutReport extends Page
{
    protected string $view = 'filament.pages.dropout-report';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|\UnitEnum|null $navigationGroup = NavGroup::CRM;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'تحليل أسباب الانسحاب';

    protected static ?string $title = 'تحليل أسباب الانسحاب (Drop-out Reasons)';

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $to = null;

    #[Url]
    public ?string $course = null;

    #[Url]
    public bool $approvedOnly = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', Lead::class) ?? false;
    }

    public function mount(): void
    {
        $this->from ??= now()->subMonths(3)->toDateString();
        $this->to ??= now()->toDateString();
    }

    protected function query(): Builder
    {
        return Lead::query()
            ->where('leads.status', LeadStatus::DroppedOut)
            ->when($this->from, fn ($q) => $q->whereDate('leads.updated_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('leads.updated_at', '<=', $this->to))
            ->when($this->course, fn ($q) => $q->where('leads.course_id', $this->course))
            ->when($this->approvedOnly, fn ($q) => $q->whereNotNull('leads.dropout_approved_at'));
    }

    public function getBreakdown(): Collection
    {
        $rows = $this->query()
            ->leftJoin('dropout_reasons', 'dropout_reasons.id', '=', 'leads.dropout_reason_id')
            ->selectRaw("COALESCE(dropout_reasons.name, 'غير محدد') as reason, COUNT(*) as total")
            ->groupBy('reason')
            ->orderByDesc('total')
            ->get();

        $sum = max(1, $rows->sum('total'));

        return $rows->map(fn ($row) => [
            'reason' => $row->reason,
            'total' => (int) $row->total,
            'percent' => round($row->total / $sum * 100, 1),
        ]);
    }

    public function getStats(): array
    {
        $all = Lead::query()
            ->when($this->from, fn ($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('created_at', '<=', $this->to))
            ->count();
        $dropped = $this->query()->count();

        return [
            'dropped' => $dropped,
            'rate' => $all > 0 ? round($dropped / $all * 100, 1) : 0,
            'pending_approval' => $this->query()->whereNull('dropout_approved_at')->count(),
            'reactivatable' => $this->query()->whereNotNull('phone')->count(),
        ];
    }

    public function getLeads(): Collection
    {
        return $this->query()->with(['dropoutReason', 'course', 'assignee'])->latest('updated_at')->limit(50)->get();
    }

    public function getCourses(): Collection
    {
        return Course::orderBy('title')->pluck('title', 'id');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('تصدير CSV لإعادة الاستهداف')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => $this->exportCsv()),
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $leads = $this->query()->with(['dropoutReason', 'course', 'assignee'])->get();

        return response()->streamDownload(function () use ($leads) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel renders Arabic correctly
            fputcsv($out, ['الاسم', 'الهاتف', 'البريد', 'الكورس', 'السبب', 'التفاصيل', 'المسؤول', 'معتمد', 'التاريخ']);

            foreach ($leads as $lead) {
                fputcsv($out, [
                    $lead->name, $lead->phone, $lead->email, $lead->course?->title, $lead->dropoutReason?->name,
                    $lead->dropout_notes, $lead->assignee?->name, $lead->dropout_approved_at ? 'نعم' : 'لا',
                    $lead->updated_at->toDateString(),
                ]);
            }

            fclose($out);
        }, 'dropouts-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
