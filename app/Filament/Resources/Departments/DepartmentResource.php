<?php

namespace App\Filament\Resources\Departments;

use App\Filament\Resources\Departments\Pages\ManageDepartments;
use App\Filament\Support\NavGroup;
use App\Models\Department;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

/** Multi-department readiness: add Italian, German… from here without code changes. */
class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-language';

    protected static string|\UnitEnum|null $navigationGroup = NavGroup::SETTINGS;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'قسم';

    protected static ?string $pluralModelLabel = 'الأقسام واللغات';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('اسم القسم')->required(),
            TextInput::make('name_en')->label('الاسم بالإنجليزية'),
            TextInput::make('slug')->label('الرابط')->required()->unique(ignoreRecord: true)->extraInputAttributes(['dir' => 'ltr']),
            TextInput::make('language_code')->label('كود اللغة')->placeholder('en, de, it…')->maxLength(8),
            ColorPicker::make('color')->label('اللون')->default('#0A5A9E'),
            TextInput::make('sort')->label('الترتيب')->numeric()->default(0),
            Textarea::make('description')->label('الوصف')->rows(2)->columnSpanFull(),
            Toggle::make('is_active')->label('مفعّل')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                ColorColumn::make('color')->label(''),
                TextColumn::make('name')->label('القسم')->description(fn ($record) => $record->name_en)->weight('medium'),
                TextColumn::make('language_code')->label('اللغة')->badge()->color('gray')->placeholder('—'),
                TextColumn::make('courses_count')->label('الكورسات')->counts('courses'),
                TextColumn::make('leads_count')->label('التسجيلات')->counts('leads'),
                ToggleColumn::make('is_active')->label('مفعّل'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageDepartments::route('/')];
    }
}
