<?php

namespace App\Filament\Resources\Groups\Pages;

use App\Filament\Resources\Groups\GroupResource;
use App\Jobs\AssignTaskTemplateJob;
use App\Models\TaskTemplate;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewGroup extends ViewRecord
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('applyTemplate')
                ->label('إسناد قالب مهام للدفعة')
                ->icon('heroicon-o-clipboard-document-list')
                ->modalDescription(fn () => 'سيتم إنشاء مهام القالب لكل طلاب المجموعة ('.$this->record->students()->count().' طالب) في الخلفية.')
                ->schema([
                    Select::make('template')->label('القالب')->options(TaskTemplate::pluck('name', 'id'))->required(),
                    DatePicker::make('start_on')->label('بداية الأسبوع الأول')->default($this->record->starts_on ?? now()),
                ])
                ->authorize(fn () => auth()->user()->can('update', $this->record))
                ->action(function (array $data) {
                    AssignTaskTemplateJob::dispatch(TaskTemplate::findOrFail($data['template']), $this->record, auth()->user(), $data['start_on']);

                    Notification::make()->title('بدأ إسناد المهام')->body('ستصلك رسالة عند الانتهاء.')->success()->send();
                }),
            EditAction::make()->color('gray'),
        ];
    }
}
