<?php

namespace App\Filament\Pages;

use App\Enums\LeadStatus;
use App\Enums\Role;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Support\NavGroup;
use App\Models\DropoutReason;
use App\Models\Lead;
use App\Models\User;
use App\Services\Crm\LeadPipeline;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;

/** Drag-and-drop kanban over the six pipeline stages, with a live SLA countdown per card. */
class PipelineBoard extends Page
{
    protected string $view = 'filament.pages.pipeline-board';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-view-columns';

    protected static string|\UnitEnum|null $navigationGroup = NavGroup::CRM;

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'لوحة مراحل الطلاب';

    protected static ?string $title = 'مسار حالات الطلاب (Pipeline)';

    #[Url]
    public ?string $assignee = null;

    #[Url]
    public string $search = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', Lead::class) ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'اسحب بطاقة الطالب بين المراحل. يبدأ مؤشر الـ SLA (24 ساعة) من لحظة التسجيل ويتوقف عند أول تواصل.';
    }

    /** @return Collection<string, Collection<int, Lead>> */
    public function getColumns(): Collection
    {
        $leads = Lead::query()
            ->with(['course:id,title', 'assignee:id,name', 'dropoutReason:id,name'])
            ->when($this->assignee, fn ($q) => $q->where('assigned_to', $this->assignee))
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%")))
            ->orderByDesc('is_overdue')
            ->orderBy('sla_due_at')
            ->latest('updated_at')
            ->limit(400)
            ->get()
            ->groupBy(fn (Lead $lead) => $lead->status->value);

        return collect(LeadStatus::cases())->mapWithKeys(fn (LeadStatus $s) => [$s->value => $leads->get($s->value, collect())]);
    }

    public function getAssignees(): Collection
    {
        return User::role([Role::Sales->value, Role::SuperAdmin->value])->orderBy('name')->pluck('name', 'id');
    }

    public function moveLead(int $leadId, string $status): void
    {
        $lead = Lead::findOrFail($leadId);
        $target = LeadStatus::from($status);
        abort_unless(auth()->user()->can('update', $lead), 403);

        if ($lead->status === $target) {
            return;
        }

        // These stages need extra data, so they open a small form instead of moving straight away.
        if ($target === LeadStatus::DroppedOut || $target === LeadStatus::AssessmentScheduled) {
            $this->mountAction('completeMove', ['lead' => $leadId, 'status' => $status]);

            return;
        }

        app(LeadPipeline::class)->transition($lead, $target, auth()->user());

        Notification::make()->title("نُقل {$lead->name} إلى «{$target->getLabel()}»")->success()->send();
    }

    public function completeMoveAction(): Action
    {
        return Action::make('completeMove')
            ->modalHeading(fn (array $arguments) => LeadStatus::from($arguments['status'])->getLabel())
            ->modalWidth('md')
            ->schema(fn (array $arguments) => LeadStatus::from($arguments['status']) === LeadStatus::DroppedOut
                ? [
                    Select::make('dropout_reason_id')->label('سبب الانسحاب')->options(DropoutReason::orderBy('sort')->pluck('name', 'id'))->required(),
                    Textarea::make('dropout_notes')->label('تفاصيل لإعادة الاستهداف')->rows(2),
                ]
                : [
                    DateTimePicker::make('assessment_at')->label('موعد اختبار المستوى')->seconds(false)->required()->minDate(now()->startOfDay()),
                ])
            ->action(function (array $data, array $arguments) {
                $lead = Lead::findOrFail($arguments['lead']);
                abort_unless(auth()->user()->can('update', $lead), 403);

                try {
                    app(LeadPipeline::class)->transition($lead, LeadStatus::from($arguments['status']), auth()->user(), $data);
                } catch (ValidationException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title('تم تحديث الحالة')->success()->send();
            });
    }

    public function leadUrl(Lead $lead): string
    {
        return LeadResource::getUrl('view', ['record' => $lead]);
    }
}
