<?php

namespace App\Filament\Resources\DropoutReasons;

use App\Filament\Resources\DropoutReasons\Pages\ManageDropoutReasons;
use App\Filament\Support\NavGroup;
use App\Models\DropoutReason;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DropoutReasonResource extends Resource
{
    protected static ?string $model = DropoutReason::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-no-symbol';

    protected static string|\UnitEnum|null $navigationGroup = NavGroup::SETTINGS;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'سبب انسحاب';

    protected static ?string $pluralModelLabel = 'أسباب الانسحاب';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('السبب')->required(),
            TextInput::make('sort')->label('الترتيب')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')->label('السبب')->weight('medium'),
                TextColumn::make('leads_count')->label('عدد الحالات')->counts('leads'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageDropoutReasons::route('/')];
    }
}
