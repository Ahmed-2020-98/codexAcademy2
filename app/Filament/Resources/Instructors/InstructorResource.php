<?php

namespace App\Filament\Resources\Instructors;

use App\Filament\Resources\Instructors\Pages\CreateInstructor;
use App\Filament\Resources\Instructors\Pages\EditInstructor;
use App\Filament\Resources\Instructors\Pages\ListInstructors;
use App\Filament\Resources\Instructors\Pages\ViewInstructor;
use App\Filament\Resources\Instructors\RelationManagers\AttendanceLogsRelationManager;
use App\Filament\Resources\Instructors\RelationManagers\UpskillingTasksRelationManager;
use App\Filament\Support\NavGroup;
use App\Models\InstructorProfile;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InstructorResource extends Resource
{
    protected static ?string $model = InstructorProfile::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string|\UnitEnum|null $navigationGroup = NavGroup::INSTRUCTORS;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'محاضر';

    protected static ?string $pluralModelLabel = 'المحاضرون والأداء';

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->isSuperAdmin() ? 'المحاضرون والأداء' : 'ملفي التعريفي وأدائي';
    }

    /** An instructor only ever sees their own profile. */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()->with('user')
            ->when($user && ! $user->isSuperAdmin(), fn ($q) => $q->where('user_id', $user->id));
    }

    public static function form(Schema $schema): Schema
    {
        $isAdmin = fn () => auth()->user()?->isSuperAdmin();

        return $schema->components([
            Section::make('الحساب')->columns(3)->visibleOn('create')->schema([
                TextInput::make('account.name')->label('الاسم')->required(),
                TextInput::make('account.email')->label('البريد')->email()->required()->unique('users', 'email'),
                TextInput::make('account.phone')->label('الهاتف')->tel(),
            ]),
            Section::make('السيرة الذاتية والخبرات')->icon('heroicon-o-identification')->columns(2)->schema([
                TextInput::make('headline')->label('المسمى المختصر')->columnSpanFull(),
                Textarea::make('bio')->label('نبذة تعريفية')->rows(4)->columnSpanFull(),
                TextInput::make('experience_years')->label('سنوات الخبرة')->numeric()->minValue(0),
                Select::make('department_id')->label('القسم')->relationship('department', 'name')->disabled(fn () => ! $isAdmin()),
                TagsInput::make('specialties')->label('التخصصات'),
                TagsInput::make('qualifications')->label('الشهادات والمؤهلات'),
                TextInput::make('slug')->label('رابط الصفحة')->required()->unique(ignoreRecord: true)->extraInputAttributes(['dir' => 'ltr'])->disabled(fn () => ! $isAdmin())->dehydrated(),
            ]),
            Section::make('الفيديو التعريفي والمحاضرة التجريبية')
                ->description('أدخل معرّفات الفيديو من مكتبة Bunny Stream.')
                ->icon('heroicon-o-video-camera')
                ->columns(2)
                ->schema([
                    TextInput::make('intro_video_id')->label('فيديو تعريفي (1 - 2 دقيقة)')->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('demo_video_id')->label('محاضرة تجريبية (5 - 10 دقائق)')->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('demo_video_title')->label('عنوان المحاضرة التجريبية')->columnSpanFull(),
                ]),
            Section::make('الإعدادات الإدارية')->columns(3)->visible($isAdmin)->schema([
                TextInput::make('hourly_rate')->label('الأجر المعتمد للساعة')->numeric()->prefix('ج.م')->required(),
                TextInput::make('rating')->label('التقييم العام')->numeric()->minValue(0)->maxValue(5)->step(0.1),
                Toggle::make('is_published')->label('ظاهر في الموقع')->default(true),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(4)->schema([
                TextEntry::make('user.name')->label('المحاضر')->weight('bold'),
                TextEntry::make('headline')->label('المسمى'),
                TextEntry::make('department.name')->label('القسم'),
                TextEntry::make('hourly_rate')->label('أجر الساعة')->money('EGP')->visible(fn () => auth()->user()->isSuperAdmin()),
                TextEntry::make('specialties')->label('التخصصات')->badge(),
                TextEntry::make('rating')->label('التقييم')->suffix(' / 5'),
                TextEntry::make('user.phone')->label('الهاتف')->extraAttributes(['dir' => 'ltr']),
                TextEntry::make('slug')->label('الصفحة العامة')->url(fn ($record) => route('instructors.show', $record))->openUrlInNewTab()->formatStateUsing(fn () => 'فتح الملف التعريفي'),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('المحاضر')->description(fn ($record) => $record->headline)->searchable()->weight('medium'),
                TextColumn::make('department.name')->label('القسم')->badge()->color('gray'),
                TextColumn::make('hourly_rate')->label('أجر الساعة')->numeric(0)->suffix(' ج.م')->extraAttributes(['class' => 'cx-mono'])->visible(fn () => auth()->user()->isSuperAdmin()),
                TextColumn::make('rating')->label('التقييم')->suffix(' ★')->extraAttributes(['class' => 'cx-mono']),
                TextColumn::make('courses_count')->label('الكورسات')->counts('courses'),
                ToggleColumn::make('is_published')->label('ظاهر')->disabled(fn () => ! auth()->user()->isSuperAdmin()),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [UpskillingTasksRelationManager::class, AttendanceLogsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInstructors::route('/'),
            'create' => CreateInstructor::route('/create'),
            'view' => ViewInstructor::route('/{record}'),
            'edit' => EditInstructor::route('/{record}/edit'),
        ];
    }
}
