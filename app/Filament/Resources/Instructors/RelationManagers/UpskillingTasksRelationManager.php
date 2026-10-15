<?php

namespace App\Filament\Resources\Instructors\RelationManagers;

use App\Enums\UpskillingStatus;
use App\Models\UpskillingTask;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UpskillingTasksRelationManager extends RelationManager
{
    protected static string $relationship = 'upskillingTasks';

    protected static ?string $title = 'مهام التطوير (Up-skilling)';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-academic-cap';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('title')->label('المهمة / المادة التدريبية')->required()->columnSpanFull(),
            DatePicker::make('month')->label('الشهر')->default(now()->startOfMonth())->required(),
            DatePicker::make('due_on')->label('موعد الإنجاز'),
            TextInput::make('material_url')->label('رابط المادة')->url()->columnSpanFull()->extraInputAttributes(['dir' => 'ltr']),
            Textarea::make('description')->label('التفاصيل')->rows(2)->columnSpanFull(),
            Select::make('status')->label('الحالة')->options(UpskillingStatus::class)->default(UpskillingStatus::Assigned->value),
        ]);
    }

    public function table(Table $table): Table
    {
        $isAdmin = fn () => auth()->user()->isSuperAdmin();

        return $table
            ->defaultSort('month', 'desc')
            ->columns([
                TextColumn::make('month')->label('الشهر')->date('F Y'),
                TextColumn::make('title')->label('المهمة')->wrap()->url(fn ($record) => $record->material_url, shouldOpenInNewTab: true),
                TextColumn::make('due_on')->label('الموعد')->date('d M'),
                TextColumn::make('status')->label('الحالة')->badge(),
            ])
            ->headerActions([
                CreateAction::make()->label('إسناد خطة تطوير')->visible($isAdmin)
                    ->mutateDataUsing(fn (array $data) => $data + ['assigned_by' => auth()->id()]),
            ])
            ->recordActions([
                Action::make('progress')
                    ->label('تحديث الإنجاز')
                    ->icon('heroicon-o-check')
                    ->schema([Select::make('status')->label('الحالة')->options(UpskillingStatus::class)->required()])
                    ->fillForm(fn (UpskillingTask $record) => ['status' => $record->status->value])
                    ->action(fn (UpskillingTask $record, array $data) => $record->update([
                        'status' => $data['status'],
                        'completed_at' => $data['status'] === UpskillingStatus::Done->value ? now() : null,
                    ]))
                    ->visible(fn (UpskillingTask $record) => auth()->user()->can('update', $record)),
                EditAction::make()->iconButton()->visible($isAdmin),
                DeleteAction::make()->iconButton()->visible($isAdmin),
            ]);
    }
}
