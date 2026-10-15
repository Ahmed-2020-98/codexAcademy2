<?php

namespace App\Filament\Resources\Students\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $title = 'المهام الشخصية (My Tasks)';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-check-circle';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('week_no')
            ->columns([
                TextColumn::make('week_no')->label('الأسبوع')->prefix('أسبوع '),
                TextColumn::make('title')->label('المهمة')->wrap(),
                TextColumn::make('group.name')->label('المجموعة')->placeholder('—'),
                TextColumn::make('due_on')->label('التسليم')->date('d M'),
                IconColumn::make('done')->label('منجزة')->state(fn ($record) => $record->completed_at !== null)->boolean(),
            ]);
    }
}
