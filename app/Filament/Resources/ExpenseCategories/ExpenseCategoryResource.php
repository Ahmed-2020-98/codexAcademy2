<?php

namespace App\Filament\Resources\ExpenseCategories;

use App\Filament\Resources\ExpenseCategories\Pages\ManageExpenseCategories;
use App\Filament\Support\NavGroup;
use App\Models\ExpenseCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExpenseCategoryResource extends Resource
{
    protected static ?string $model = ExpenseCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-swatch';

    protected static string|\UnitEnum|null $navigationGroup = NavGroup::SETTINGS;

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'تصنيف مصروف';

    protected static ?string $pluralModelLabel = 'تصنيفات المصروفات';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('التصنيف')->required(),
            ColorPicker::make('color')->label('اللون')->default('#0A5A9E'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color')->label(''),
                TextColumn::make('name')->label('التصنيف')->weight('medium'),
                TextColumn::make('expenses_sum_amount')->label('الإجمالي')->sum('expenses', 'amount')->numeric(0)->suffix(' ج.م')->extraAttributes(['class' => 'cx-mono']),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageExpenseCategories::route('/')];
    }
}
