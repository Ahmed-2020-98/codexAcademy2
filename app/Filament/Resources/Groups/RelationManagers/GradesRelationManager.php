<?php

namespace App\Filament\Resources\Groups\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GradesRelationManager extends RelationManager
{
    protected static string $relationship = 'grades';

    protected static ?string $title = 'الدرجات';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-academic-cap';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('user_id')->label('الطالب')->options(fn () => $this->getOwnerRecord()->students()->pluck('name', 'users.id'))->required()->searchable(),
            TextInput::make('title')->label('التقييم')->required()->default('اختبار أسبوعي'),
            TextInput::make('score')->label('الدرجة')->numeric()->required(),
            TextInput::make('max_score')->label('من')->numeric()->default(100)->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('الطالب')->searchable(),
                TextColumn::make('title')->label('التقييم'),
                TextColumn::make('score')
                    ->label('الدرجة')
                    ->formatStateUsing(fn ($state, $record) => rtrim(rtrim($state, '0'), '.').' / '.rtrim(rtrim($record->max_score, '0'), '.'))
                    ->color(fn ($record) => $record->score / max(1, $record->max_score) >= 0.7 ? 'success' : 'danger')
                    ->extraAttributes(['class' => 'cx-mono']),
                TextColumn::make('recorder.name')->label('سجّلها')->toggleable(),
                TextColumn::make('created_at')->label('التاريخ')->date('d M Y'),
            ])
            ->filters([SelectFilter::make('title')->label('التقييم')->options(fn () => $this->getOwnerRecord()->grades()->distinct()->pluck('title', 'title'))])
            ->headerActions([
                CreateAction::make()->label('إدخال درجة')->mutateDataUsing(fn (array $data) => $data + ['recorded_by' => auth()->id()]),
            ])
            ->recordActions([EditAction::make()->iconButton(), DeleteAction::make()->iconButton()]);
    }
}
