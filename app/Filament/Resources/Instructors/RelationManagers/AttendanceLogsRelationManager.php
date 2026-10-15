<?php

namespace App\Filament\Resources\Instructors\RelationManagers;

use App\Enums\AttendanceStatus;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendanceLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'attendanceLogs';

    protected static ?string $title = 'سجل الالتزام (Attendance Log)';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-calendar';

    public function isReadOnly(): bool
    {
        return ! auth()->user()->isSuperAdmin();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            DatePicker::make('date')->label('التاريخ')->default(now())->required(),
            Select::make('status')->label('الحالة')->options([
                AttendanceStatus::Absent->value => 'غياب',
                AttendanceStatus::Excused->value => 'اعتذار',
                AttendanceStatus::Late->value => 'تأخير',
            ])->required(),
            Textarea::make('reason')->label('السبب')->rows(2)->columnSpanFull(),
            Toggle::make('is_accepted')->label('سبب مقبول'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')->label('التاريخ')->date('d M Y'),
                TextColumn::make('status')->label('الحالة')->badge(),
                TextColumn::make('reason')->label('السبب')->wrap(),
                IconColumn::make('is_accepted')->label('مقبول')->boolean(),
            ])
            ->headerActions([CreateAction::make()->label('تسجيل حالة')])
            ->recordActions([EditAction::make()->iconButton(), DeleteAction::make()->iconButton()]);
    }
}
