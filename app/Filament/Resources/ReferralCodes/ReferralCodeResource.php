<?php

namespace App\Filament\Resources\ReferralCodes;

use App\Filament\Resources\ReferralCodes\Pages\ManageReferralCodes;
use App\Filament\Support\NavGroup;
use App\Models\ReferralCode;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

/** Referral program: one discount code per student, with performance tracking. */
class ReferralCodeResource extends Resource
{
    protected static ?string $model = ReferralCode::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static string|\UnitEnum|null $navigationGroup = NavGroup::STUDENTS;

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'كود إحالة';

    protected static ?string $pluralModelLabel = 'نظام الإحالة';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('discount_percent')->label('نسبة الخصم %')->numeric()->minValue(0)->maxValue(100)->required(),
            Toggle::make('is_active')->label('مفعّل'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('uses_count', 'desc')
            ->columns([
                TextColumn::make('code')->label('الكود')->copyable()->searchable()->extraAttributes(['class' => 'cx-mono', 'dir' => 'ltr'])->weight('medium'),
                TextColumn::make('user.name')->label('الطالب')->searchable(),
                TextColumn::make('discount_percent')->label('الخصم')->suffix('%'),
                TextColumn::make('uses_count')->label('مرات الاستخدام')->sortable()->badge()->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                TextColumn::make('redemptions_sum_discount_amount')->label('إجمالي الخصومات')->sum('redemptions', 'discount_amount')->numeric(0)->suffix(' ج.م')->placeholder('0'),
                ToggleColumn::make('is_active')->label('مفعّل')->disabled(fn () => ! auth()->user()->isSuperAdmin()),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageReferralCodes::route('/')];
    }
}
