<?php

namespace App\Filament\Resources\TaskTemplates;

use App\Filament\Resources\TaskTemplates\Pages\ManageTaskTemplates;
use App\Filament\Support\NavGroup;
use App\Jobs\AssignTaskTemplateJob;
use App\Models\Group;
use App\Models\TaskTemplate;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Ready-made weekly task plans that can be pushed to a whole cohort (500+ students) in one click. */
class TaskTemplateResource extends Resource
{
    protected static ?string $model = TaskTemplate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = NavGroup::STUDENTS;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'قالب مهام';

    protected static ?string $pluralModelLabel = 'قوالب المهام الجاهزة';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('اسم القالب')->required(),
            Select::make('course_id')->label('مرتبط بكورس')->relationship('course', 'title')->searchable()->preload(),
            Textarea::make('description')->label('الوصف')->rows(2)->columnSpanFull(),
            Repeater::make('items')
                ->label('المهام')
                ->relationship()
                ->orderColumn('sort')
                ->columns(6)
                ->defaultItems(3)
                ->addActionLabel('إضافة مهمة')
                ->schema([
                    TextInput::make('week_no')->label('الأسبوع')->numeric()->minValue(1)->default(1)->required()->columnSpan(1),
                    TextInput::make('title')->label('المهمة')->required()->columnSpan(5),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('القالب')->description(fn ($record) => $record->description)->weight('medium')->wrap(),
                TextColumn::make('items_count')->label('عدد المهام')->counts('items'),
                TextColumn::make('course.title')->label('الكورس')->placeholder('عام'),
                TextColumn::make('creator.name')->label('أنشأه'),
            ])
            ->recordActions([
                Action::make('apply')
                    ->label('إسناد لدفعة')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->schema([
                        Select::make('group_id')
                            ->label('المجموعة')
                            ->options(fn () => Group::query()
                                ->when(! auth()->user()->isSuperAdmin(), fn ($q) => $q->where('instructor_id', auth()->id()))
                                ->withCount('students')->get()
                                ->mapWithKeys(fn ($g) => [$g->id => "{$g->name} ({$g->students_count} طالب)"]))
                            ->searchable()
                            ->required(),
                        DatePicker::make('start_on')->label('بداية الأسبوع الأول')->default(now()->startOfWeek()),
                    ])
                    ->action(function (TaskTemplate $record, array $data) {
                        $group = Group::findOrFail($data['group_id']);
                        abort_unless(auth()->user()->can('update', $group), 403);
                        AssignTaskTemplateJob::dispatch($record, $group, auth()->user(), $data['start_on']);
                        Notification::make()->title('جارٍ إسناد المهام في الخلفية')->success()->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageTaskTemplates::route('/')];
    }
}
