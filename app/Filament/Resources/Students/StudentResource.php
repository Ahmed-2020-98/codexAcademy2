<?php

namespace App\Filament\Resources\Students;

use App\Enums\Role;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Filament\Resources\Students\Pages\ViewStudent;
use App\Filament\Resources\Students\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Resources\Students\RelationManagers\TasksRelationManager;
use App\Filament\Support\NavGroup;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'students';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = NavGroup::STUDENTS;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'طالب';

    protected static ?string $pluralModelLabel = 'الطلاب';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->role(Role::Student->value);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(4)->schema([
                TextEntry::make('name')->label('الاسم')->weight('bold'),
                TextEntry::make('email')->label('البريد')->copyable(),
                TextEntry::make('phone')->label('الهاتف')->extraAttributes(['dir' => 'ltr']),
                TextEntry::make('referralCode.code')->label('كود الإحالة')->badge()->color('info'),
                TextEntry::make('enrollments_count')->label('الكورسات')->state(fn (User $record) => $record->enrollments()->count()),
                TextEntry::make('avg_progress')->label('متوسط التقدم')->state(fn (User $record) => round((float) $record->enrollments()->avg('progress')).'%'),
                TextEntry::make('tasks_done')->label('إنجاز المهام')->state(function (User $record) {
                    $total = $record->tasks()->count();

                    return $total ? round($record->tasks()->whereNotNull('completed_at')->count() / $total * 100).'%' : '—';
                }),
                TextEntry::make('created_at')->label('تاريخ الانضمام')->date('d M Y'),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('الطالب')->description(fn (User $record) => $record->email)->searchable(['name', 'email', 'phone'])->weight('medium'),
                TextColumn::make('phone')->label('الهاتف')->extraAttributes(['dir' => 'ltr', 'class' => 'cx-mono']),
                TextColumn::make('enrollments_count')->label('الكورسات')->counts('enrollments')->sortable(),
                TextColumn::make('enrollments_avg_progress')->label('متوسط التقدم')->avg('enrollments', 'progress')->numeric(0)->suffix('%')->sortable(),
                TextColumn::make('referralCode.code')->label('كود الإحالة')->extraAttributes(['class' => 'cx-mono', 'dir' => 'ltr'])->toggleable(),
                TextColumn::make('created_at')->label('الانضمام')->date('d M Y')->sortable()->toggleable(),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getRelations(): array
    {
        return [EnrollmentsRelationManager::class, TasksRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudents::route('/'),
            'view' => ViewStudent::route('/{record}'),
        ];
    }
}
