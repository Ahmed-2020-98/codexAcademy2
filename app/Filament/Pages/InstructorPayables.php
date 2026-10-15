<?php

namespace App\Filament\Pages;

use App\Filament\Support\NavGroup;
use App\Models\InstructorPayout;
use App\Models\User;
use App\Services\Finance\InstructorPayableCalculator;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/** Instructor dues computed from completed session hours × approved hourly rate. */
class InstructorPayables extends Page
{
    protected string $view = 'filament.pages.instructor-payables';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static string|\UnitEnum|null $navigationGroup = NavGroup::FINANCE;

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'مستحقات المحاضرين';

    protected static ?string $title = 'مستحقات المحاضرين';

    #[Url]
    public ?string $month = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->month ??= now()->format('Y-m');
    }

    public function getSubheading(): ?string
    {
        return 'يتم الاحتساب آلياً: عدد ساعات المحاضرات المكتملة × الأجر المعتمد للساعة (Hourly Rate).';
    }

    protected function period(): array
    {
        $start = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();

        return [$start, $start->copy()->endOfMonth()->min(now()->endOfDay())];
    }

    public function getRows(): Collection
    {
        [$from, $to] = $this->period();

        return app(InstructorPayableCalculator::class)->forPeriod($from, $to);
    }

    public function getMonths(): array
    {
        return collect(range(0, 11))->mapWithKeys(function ($i) {
            $m = now()->startOfMonth()->subMonths($i);

            return [$m->format('Y-m') => $m->translatedFormat('F Y')];
        })->all();
    }

    public function payAction(): Action
    {
        return Action::make('pay')
            ->label('تسجيل الصرف')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments) => 'صرف مستحقات '.User::find($arguments['instructor'])?->name)
            ->modalDescription(function (array $arguments) {
                $row = $this->getRows()->firstWhere('instructor.id', $arguments['instructor']);

                return 'سيتم تسجيل صرف '.number_format($row['outstanding'] ?? 0, 2).' ج.م عن '.number_format($row['hours'] ?? 0, 1).' ساعة.';
            })
            ->schema([Textarea::make('notes')->label('ملاحظات / رقم التحويل')->rows(2)])
            ->action(function (array $arguments, array $data) {
                [$from, $to] = $this->period();
                $row = $this->getRows()->firstWhere('instructor.id', $arguments['instructor']);

                if (! $row || $row['outstanding'] <= 0) {
                    return;
                }

                InstructorPayout::create([
                    'instructor_id' => $arguments['instructor'],
                    'period_start' => $from->toDateString(),
                    'period_end' => $to->toDateString(),
                    'hours' => $row['hours'],
                    'amount' => $row['outstanding'],
                    'paid_at' => now(),
                    'notes' => $data['notes'] ?? null,
                ]);

                Notification::make()->title('تم تسجيل الصرف')->success()->send();
            });
    }
}
