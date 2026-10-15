<?php

namespace App\Filament\Resources\Groups\RelationManagers;

use App\Enums\Role;
use App\Models\Attendance;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StudentsRelationManager extends RelationManager
{
    protected static string $relationship = 'students';

    protected static ?string $title = 'الطلاب';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-users';

    public function table(Table $table): Table
    {
        $groupId = $this->getOwnerRecord()->id;

        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('الطالب')->searchable()->weight('medium'),
                TextColumn::make('phone')->label('الهاتف')->extraAttributes(['dir' => 'ltr', 'class' => 'cx-mono']),
                TextColumn::make('attendance')
                    ->label('نسبة الحضور')
                    ->state(function ($record) use ($groupId) {
                        $rows = $record->hasMany(Attendance::class)
                            ->whereHas('session', fn ($q) => $q->where('group_id', $groupId))
                            ->pluck('status');

                        return $rows->count() ? round($rows->filter(fn ($s) => in_array($s->value, ['present', 'late']))->count() / $rows->count() * 100).'%' : '—';
                    })
                    ->extraAttributes(['class' => 'cx-mono']),
                TextColumn::make('tasks_progress')
                    ->label('إنجاز المهام')
                    ->state(function ($record) use ($groupId) {
                        $tasks = $record->tasks()->where('group_id', $groupId);
                        $total = (clone $tasks)->count();

                        return $total ? round((clone $tasks)->whereNotNull('completed_at')->count() / $total * 100).'%' : '—';
                    })
                    ->extraAttributes(['class' => 'cx-mono']),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('إضافة طالب')
                    ->recordSelectOptionsQuery(fn ($query) => $query->role(Role::Student->value))
                    ->recordSelectSearchColumns(['name', 'email', 'phone'])
                    ->preloadRecordSelect()
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
            ])
            ->recordActions([
                DetachAction::make()->label('إزالة')->visible(fn () => auth()->user()->isSuperAdmin()),
            ]);
    }
}
