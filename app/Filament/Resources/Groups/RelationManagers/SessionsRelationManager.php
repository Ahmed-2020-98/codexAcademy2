<?php

namespace App\Filament\Resources\Groups\RelationManagers;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Models\GroupSession;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    protected static ?string $title = 'الجلسات والحضور';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-calendar-days';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('title')->label('عنوان الجلسة')->required(),
            DateTimePicker::make('starts_at')->label('الموعد')->seconds(false)->required(),
            TextInput::make('duration_hours')->label('المدة (ساعات)')->numeric()->step(0.5)->default(2)->required(),
            Select::make('status')->label('الحالة')->options(SessionStatus::class)->default(SessionStatus::Scheduled->value)->required(),
            TextInput::make('meeting_url')->label('رابط خاص بهذه الجلسة')->url()->columnSpanFull()->extraInputAttributes(['dir' => 'ltr']),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at')
            ->columns([
                TextColumn::make('title')->label('الجلسة')->weight('medium'),
                TextColumn::make('starts_at')->label('الموعد')->dateTime('D d M Y — h:i A')->sortable(),
                TextColumn::make('duration_hours')->label('المدة')->suffix(' س')->extraAttributes(['class' => 'cx-mono']),
                TextColumn::make('status')->label('الحالة')->badge(),
                TextColumn::make('attendance_rate')
                    ->label('الحضور')
                    ->state(function (GroupSession $record) {
                        $total = $record->attendances()->count();

                        return $total ? round($record->attendances()->whereIn('status', ['present', 'late'])->count() / $total * 100).'%' : '—';
                    })
                    ->extraAttributes(['class' => 'cx-mono']),
            ])
            ->headerActions([CreateAction::make()->label('إضافة جلسة')])
            ->recordActions([
                $this->attendanceAction(),
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
            ]);
    }

    protected function attendanceAction(): Action
    {
        return Action::make('attendance')
            ->label('تسجيل الحضور')
            ->icon('heroicon-o-clipboard-document-check')
            ->color('primary')
            ->modalHeading(fn (GroupSession $record) => 'حضور: '.$record->title)
            ->modalWidth('3xl')
            ->fillForm(function (GroupSession $record) {
                $existing = $record->attendances()->pluck('status', 'user_id');

                return [
                    'mark_completed' => $record->status !== SessionStatus::Cancelled,
                    'attendance' => $record->group->students->mapWithKeys(fn ($s) => [
                        $s->id => ($existing[$s->id] ?? AttendanceStatus::Present)->value ?? 'present',
                    ])->all(),
                ];
            })
            ->schema(fn (GroupSession $record) => [
                Toggle::make('mark_completed')->label('اعتبار الجلسة مكتملة (تُحتسب ضمن ساعات المحاضر)'),
                ...$record->group->students->map(fn ($student) => ToggleButtons::make("attendance.{$student->id}")
                    ->label($student->name)
                    ->options(AttendanceStatus::class)
                    ->inline()
                    ->grouped()
                    ->required())->all(),
            ])
            ->action(function (GroupSession $record, array $data) {
                foreach ($data['attendance'] ?? [] as $userId => $status) {
                    $record->attendances()->updateOrCreate(['user_id' => $userId], ['status' => $status]);
                }

                if ($data['mark_completed'] ?? false) {
                    $record->update(['status' => SessionStatus::Completed]);
                }

                Notification::make()->title('تم حفظ الحضور')->success()->send();
            });
    }
}
