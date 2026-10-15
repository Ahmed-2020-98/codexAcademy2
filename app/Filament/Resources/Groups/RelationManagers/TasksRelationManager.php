<?php

namespace App\Filament\Resources\Groups\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $title = 'لوحة مهام الطلاب';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-check-circle';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('user_id')->label('الطالب')->options(fn () => $this->getOwnerRecord()->students()->pluck('name', 'users.id'))->required()->searchable(),
            TextInput::make('week_no')->label('الأسبوع')->numeric()->minValue(1)->default(1)->required(),
            TextInput::make('title')->label('المهمة')->required()->columnSpanFull(),
            Textarea::make('description')->label('التفاصيل')->rows(2)->columnSpanFull(),
            DatePicker::make('due_on')->label('تاريخ التسليم'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('week_no')
            ->defaultGroup('week_no')
            ->columns([
                TextColumn::make('user.name')->label('الطالب')->searchable(),
                TextColumn::make('title')->label('المهمة')->wrap(),
                TextColumn::make('week_no')->label('الأسبوع')->prefix('أسبوع ')->sortable(),
                TextColumn::make('due_on')->label('التسليم')->date('d M'),
                IconColumn::make('done')->label('منجزة')->state(fn ($record) => $record->completed_at !== null)->boolean(),
            ])
            ->filters([
                SelectFilter::make('week_no')->label('الأسبوع')->options(fn () => $this->getOwnerRecord()->tasks()->distinct()->orderBy('week_no')->pluck('week_no', 'week_no')->map(fn ($w) => "الأسبوع {$w}")),
                TernaryFilter::make('completed_at')->label('الإنجاز')->nullable()->trueLabel('منجزة')->falseLabel('غير منجزة'),
            ])
            ->headerActions([
                CreateAction::make()->label('إسناد مهمة')->mutateDataUsing(fn (array $data) => $data + ['assigned_by' => auth()->id()]),
            ])
            ->recordActions([EditAction::make()->iconButton(), DeleteAction::make()->iconButton()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
