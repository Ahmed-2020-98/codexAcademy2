<?php

namespace App\Filament\Resources\Staff;

use App\Enums\Role;
use App\Filament\Resources\Staff\Pages\ManageStaff;
use App\Filament\Support\NavGroup;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Back-office accounts and their role in the RBAC matrix. Super Admin only. */
class StaffResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'staff';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = NavGroup::SETTINGS;

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'مستخدم';

    protected static ?string $pluralModelLabel = 'المستخدمون والصلاحيات';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canViewAny() && $record->id !== auth()->id();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->role(Role::staff())->with('roles');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('الاسم')->required(),
            TextInput::make('email')->label('البريد')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('phone')->label('الهاتف')->tel(),
            Select::make('roles')
                ->label('الدور')
                ->relationship('roles', 'name', fn ($query) => $query->whereIn('name', Role::staff()))
                ->getOptionLabelFromRecordUsing(fn ($record) => Role::from($record->name)->getLabel())
                ->required()
                ->preload(),
            TextInput::make('password')->label('كلمة المرور')->password()->revealable()
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn ($state) => filled($state)),
            Toggle::make('is_active')->label('الحساب مفعّل')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('الاسم')->description(fn (User $record) => $record->email)->searchable(['name', 'email'])->weight('medium'),
                TextColumn::make('roles.name')->label('الدور')->badge()
                    ->formatStateUsing(fn ($state) => Role::tryFrom($state)?->getLabel() ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'super_admin' => 'danger', 'sales' => 'warning', default => 'info'
                    }),
                TextColumn::make('phone')->label('الهاتف')->extraAttributes(['dir' => 'ltr']),
                ToggleColumn::make('is_active')->label('مفعّل'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageStaff::route('/')];
    }
}
