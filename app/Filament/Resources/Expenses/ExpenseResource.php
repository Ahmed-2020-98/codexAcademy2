<?php

namespace App\Filament\Resources\Expenses;

use App\Filament\Resources\Expenses\Pages\ManageExpenses;
use App\Filament\Support\NavGroup;
use App\Models\Expense;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Operating expenses log (hall rent, ad campaigns, server subscriptions…). */
class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static string|\UnitEnum|null $navigationGroup = NavGroup::FINANCE;

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'مصروف';

    protected static ?string $pluralModelLabel = 'سجل المصروفات';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('title')->label('البيان')->required()->columnSpanFull(),
            Select::make('expense_category_id')->label('التصنيف')->relationship('category', 'name')->required()->preload()
                ->createOptionForm([TextInput::make('name')->label('التصنيف')->required()]),
            TextInput::make('amount')->label('المبلغ')->numeric()->required()->prefix('ج.م'),
            DatePicker::make('spent_on')->label('التاريخ')->default(now())->required(),
            Textarea::make('notes')->label('ملاحظات')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('spent_on', 'desc')
            ->columns([
                TextColumn::make('spent_on')->label('التاريخ')->date('d M Y')->sortable(),
                TextColumn::make('title')->label('البيان')->searchable()->wrap(),
                TextColumn::make('category.name')->label('التصنيف')->badge()->color('gray'),
                TextColumn::make('amount')->label('المبلغ')->numeric(2)->suffix(' ج.م')->sortable()->extraAttributes(['class' => 'cx-mono'])
                    ->summarize(Sum::make()->label('الإجمالي')->numeric(2)),
                TextColumn::make('creator.name')->label('أدخله')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('expense_category_id')->label('التصنيف')->relationship('category', 'name')->multiple(),
                Filter::make('this_month')->label('هذا الشهر')->toggle()
                    ->query(fn (Builder $q) => $q->whereBetween('spent_on', [now()->startOfMonth(), now()->endOfMonth()])),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageExpenses::route('/')];
    }
}
