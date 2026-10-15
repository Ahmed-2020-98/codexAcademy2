<?php

namespace App\Filament\Resources\Courses;

use App\Enums\CourseType;
use App\Enums\Role;
use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\Pages\ListCourses;
use App\Filament\Resources\Courses\RelationManagers\GroupsRelationManager;
use App\Filament\Resources\Courses\RelationManagers\LessonsRelationManager;
use App\Filament\Support\NavGroup;
use App\Models\Course;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = NavGroup::ACADEMICS;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'كورس';

    protected static ?string $pluralModelLabel = 'الكورسات';

    protected static ?string $recordTitleAttribute = 'title';

    /** Instructors only ever see the courses they teach. */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->when($user && ! $user->isSuperAdmin(), fn ($q) => $q->where('instructor_id', $user->id));
    }

    public static function form(Schema $schema): Schema
    {
        $isAdmin = fn () => auth()->user()?->isSuperAdmin();

        return $schema->components([
            Section::make('البيانات الأساسية')
                ->icon('heroicon-o-book-open')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('اسم الكورس')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, Get $get, ?string $state) => blank($get('slug')) ? $set('slug', Str::slug(Str::ascii((string) $state))) : null),
                    TextInput::make('slug')->label('الرابط (slug)')->required()->unique(ignoreRecord: true)->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('subtitle')->label('وصف مختصر')->columnSpanFull(),
                    Textarea::make('description')->label('الوصف التفصيلي')->rows(4)->columnSpanFull(),
                    TagsInput::make('outcomes')->label('مخرجات التعلم')->placeholder('أضف مخرجاً واضغط Enter')->columnSpanFull(),
                ]),
            Section::make('التصنيف والتسعير')
                ->icon('heroicon-o-tag')
                ->columns(3)
                ->schema([
                    Select::make('department_id')->label('القسم')->relationship('department', 'name')->required()->preload()->disabled(fn () => ! $isAdmin()),
                    Select::make('type')->label('نوع الكورس')->options(CourseType::class)->required()->live()->disabled(fn () => ! $isAdmin()),
                    Select::make('instructor_id')
                        ->label('المحاضر')
                        ->options(fn () => User::role(Role::Instructor->value)->pluck('name', 'id'))
                        ->searchable()
                        ->disabled(fn () => ! $isAdmin()),
                    TextInput::make('level')->label('المستوى'),
                    TextInput::make('duration_hours')->label('عدد الساعات')->numeric()->minValue(0),
                    TextInput::make('passing_score')->label('درجة النجاح %')->numeric()->default(70)->minValue(0)->maxValue(100),
                    TextInput::make('price')->label('السعر')->numeric()->required()->prefix('ج.م')->disabled(fn () => ! $isAdmin()),
                    TextInput::make('sale_price')->label('سعر العرض')->numeric()->prefix('ج.م')->disabled(fn () => ! $isAdmin()),
                    TextInput::make('location')
                        ->label('القاعة / العنوان')
                        ->visible(fn (Get $get) => in_array($get('type'), [CourseType::Offline->value, CourseType::Offline], true)),
                    Toggle::make('is_published')->label('منشور')->default(true)->disabled(fn () => ! $isAdmin()),
                    Toggle::make('is_featured')->label('مميز في الصفحة الرئيسية')->disabled(fn () => ! $isAdmin()),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('الكورس')->description(fn (Course $record) => $record->department?->name)->searchable()->weight('medium')->wrap(),
                TextColumn::make('type')->label('النوع')->badge(),
                TextColumn::make('instructor.name')->label('المحاضر')->placeholder('—'),
                TextColumn::make('price')->label('السعر')->numeric(decimalPlaces: 0)->suffix(' ج.م')->extraAttributes(['class' => 'cx-mono'])->sortable(),
                TextColumn::make('enrollments_count')->label('المشتركون')->counts('enrollments')->sortable()->extraAttributes(['class' => 'cx-mono']),
                TextColumn::make('lessons_count')->label('الدروس')->counts('lessons')->toggleable(),
                ToggleColumn::make('is_published')->label('منشور')->disabled(fn () => ! auth()->user()->isSuperAdmin()),
            ])
            ->filters([
                SelectFilter::make('type')->label('النوع')->options(CourseType::class),
                SelectFilter::make('department_id')->label('القسم')->relationship('department', 'name'),
                SelectFilter::make('instructor_id')->label('المحاضر')->relationship('instructor', 'name'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [LessonsRelationManager::class, GroupsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourses::route('/'),
            'create' => CreateCourse::route('/create'),
            'edit' => EditCourse::route('/{record}/edit'),
        ];
    }
}
