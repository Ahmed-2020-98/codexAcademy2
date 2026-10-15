<?php

namespace App\Filament\Resources\Groups;

use App\Enums\CourseType;
use App\Enums\Role;
use App\Filament\Resources\Groups\Pages\CreateGroup;
use App\Filament\Resources\Groups\Pages\EditGroup;
use App\Filament\Resources\Groups\Pages\ListGroups;
use App\Filament\Resources\Groups\Pages\ViewGroup;
use App\Filament\Resources\Groups\RelationManagers\GradesRelationManager;
use App\Filament\Resources\Groups\RelationManagers\SessionsRelationManager;
use App\Filament\Resources\Groups\RelationManagers\StudentsRelationManager;
use App\Filament\Resources\Groups\RelationManagers\TasksRelationManager;
use App\Filament\Support\NavGroup;
use App\Models\Course;
use App\Models\Group;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = NavGroup::ACADEMICS;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'مجموعة';

    protected static ?string $pluralModelLabel = 'المجموعات والجلسات';

    protected static ?string $recordTitleAttribute = 'name';

    /** Instructors: "their groups only" per the RBAC matrix. */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->when($user && ! $user->isSuperAdmin(), fn ($q) => $q->where('instructor_id', $user->id));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات المجموعة')->columns(2)->schema([
                Select::make('course_id')
                    ->label('الكورس')
                    ->options(fn () => Course::where('type', '!=', CourseType::Recorded)->pluck('title', 'id'))
                    ->default(fn () => request()->integer('course') ?: null)
                    ->required()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (Set $set, $state) {
                        $course = Course::find($state);
                        $set('mode', $course?->type?->value);
                        $set('instructor_id', $course?->instructor_id);
                        $set('location', $course?->location);
                    }),
                TextInput::make('name')->label('اسم المجموعة / الدفعة')->required(),
                Select::make('mode')->label('نمط الحضور')->options([CourseType::Live->value => 'لايف (أونلاين)', CourseType::Offline->value => 'أوفلاين (حضوري)'])->required(),
                Select::make('instructor_id')->label('المحاضر')->options(fn () => User::role(Role::Instructor->value)->pluck('name', 'id'))->required(),
                TextInput::make('capacity')->label('السعة')->numeric()->default(25),
                Select::make('status')->label('الحالة')->options(['active' => 'نشطة', 'completed' => 'منتهية', 'paused' => 'متوقفة'])->default('active'),
                DatePicker::make('starts_on')->label('تاريخ البداية'),
                DatePicker::make('ends_on')->label('تاريخ النهاية'),
            ]),
            Section::make('روابط الحضور')->columns(3)->schema([
                TextInput::make('meeting_url')->label('رابط Zoom / Google Meet')->url()->extraInputAttributes(['dir' => 'ltr']),
                TextInput::make('whatsapp_url')->label('رابط مجموعة واتساب')->url()->extraInputAttributes(['dir' => 'ltr']),
                TextInput::make('location')->label('القاعة (أوفلاين)'),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(4)->schema([
                TextEntry::make('course.title')->label('الكورس'),
                TextEntry::make('instructor.name')->label('المحاضر'),
                TextEntry::make('mode')->label('النمط')->badge(),
                TextEntry::make('students_count')->label('الطلاب')->state(fn (Group $record) => $record->students()->count().' / '.$record->capacity),
                TextEntry::make('starts_on')->label('البداية')->date('d M Y'),
                TextEntry::make('ends_on')->label('النهاية')->date('d M Y'),
                TextEntry::make('meeting_url')->label('رابط الجلسات')->url(fn ($state) => $state)->openUrlInNewTab()->placeholder('—')->limit(30),
                TextEntry::make('location')->label('القاعة')->placeholder('—'),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('المجموعة')->description(fn (Group $record) => $record->course?->title)->searchable()->weight('medium')->wrap(),
                TextColumn::make('mode')->label('النمط')->badge(),
                TextColumn::make('instructor.name')->label('المحاضر'),
                TextColumn::make('students_count')->label('الطلاب')->counts('students')->extraAttributes(['class' => 'cx-mono']),
                TextColumn::make('next_session')
                    ->label('الجلسة القادمة')
                    ->state(fn (Group $record) => $record->sessions()->where('status', 'scheduled')->where('starts_at', '>=', now())->value('starts_at'))
                    ->dateTime('D d M — h:i A')
                    ->placeholder('—'),
                TextColumn::make('starts_on')->label('البداية')->date('d M Y')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('mode')->label('النمط')->options([CourseType::Live->value => 'لايف', CourseType::Offline->value => 'أوفلاين']),
                SelectFilter::make('instructor_id')->label('المحاضر')->relationship('instructor', 'name'),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            SessionsRelationManager::class,
            StudentsRelationManager::class,
            GradesRelationManager::class,
            TasksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGroups::route('/'),
            'create' => CreateGroup::route('/create'),
            'view' => ViewGroup::route('/{record}'),
            'edit' => EditGroup::route('/{record}/edit'),
        ];
    }
}
