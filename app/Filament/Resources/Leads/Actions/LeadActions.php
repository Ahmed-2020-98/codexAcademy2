<?php

namespace App\Filament\Resources\Leads\Actions;

use App\Enums\LeadStatus;
use App\Models\DropoutReason;
use App\Models\Lead;
use App\Services\Crm\LeadPipeline;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;

/** Pipeline actions shared by the leads table, the lead view page and the kanban board. */
class LeadActions
{
    public static function changeStatus(): Action
    {
        return Action::make('changeStatus')
            ->label('تغيير الحالة')
            ->icon('heroicon-o-arrows-right-left')
            ->color('primary')
            ->modalHeading(fn (Lead $record) => 'تحديث حالة: '.$record->name)
            ->modalWidth('lg')
            ->fillForm(fn (Lead $record) => [
                'status' => $record->status->value,
                'assessment_at' => $record->assessment_at,
                'assessment_level' => $record->assessment_level,
                'dropout_reason_id' => $record->dropout_reason_id,
                'dropout_notes' => $record->dropout_notes,
            ])
            ->schema([
                Select::make('status')
                    ->label('الحالة الجديدة')
                    ->options(collect(LeadStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->getLabel().' — '.$s->englishLabel()]))
                    ->required()
                    ->live(),
                DateTimePicker::make('assessment_at')
                    ->label('موعد اختبار المستوى')
                    ->seconds(false)
                    ->visible(fn (Get $get) => $get('status') === LeadStatus::AssessmentScheduled->value)
                    ->required(fn (Get $get) => $get('status') === LeadStatus::AssessmentScheduled->value),
                TextInput::make('assessment_level')
                    ->label('نتيجة / مستوى الطالب')
                    ->visible(fn (Get $get) => in_array($get('status'), [LeadStatus::AssessmentCompleted->value, LeadStatus::Enrolled->value], true)),
                Select::make('dropout_reason_id')
                    ->label('سبب الانسحاب / عدم الاشتراك')
                    ->options(fn () => DropoutReason::orderBy('sort')->pluck('name', 'id'))
                    ->visible(fn (Get $get) => $get('status') === LeadStatus::DroppedOut->value)
                    ->required(fn (Get $get) => $get('status') === LeadStatus::DroppedOut->value),
                Textarea::make('dropout_notes')
                    ->label('تفاصيل إضافية لإعادة الاستهداف')
                    ->rows(2)
                    ->visible(fn (Get $get) => $get('status') === LeadStatus::DroppedOut->value),
                Textarea::make('note')
                    ->label('ملاحظة على التحديث')
                    ->rows(2),
            ])
            ->action(function (Lead $record, array $data) {
                app(LeadPipeline::class)->transition($record, LeadStatus::from($data['status']), auth()->user(), $data);

                Notification::make()->title('تم تحديث حالة الطالب')->success()->send();
            })
            ->authorize(fn (Lead $record) => auth()->user()->can('update', $record));
    }

    public static function approveDropout(): Action
    {
        return Action::make('approveDropout')
            ->label('اعتماد سبب الانسحاب')
            ->icon('heroicon-o-shield-check')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription(fn (Lead $record) => 'السبب المُدخل: '.($record->dropoutReason?->name ?? '—'))
            ->visible(fn (Lead $record) => $record->status === LeadStatus::DroppedOut && $record->dropout_approved_at === null)
            ->authorize(fn (Lead $record) => auth()->user()->can('approveDropout', $record))
            ->action(function (Lead $record) {
                app(LeadPipeline::class)->approveDropout($record, auth()->user());
                Notification::make()->title('تم الاعتماد')->success()->send();
            });
    }

    public static function whatsapp(): Action
    {
        return Action::make('whatsapp')
            ->label('واتساب')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->color('success')
            ->url(fn (Lead $record) => 'https://wa.me/'.ltrim((string) $record->phone, '+'), shouldOpenInNewTab: true)
            ->visible(fn (Lead $record) => filled($record->phone));
    }
}
