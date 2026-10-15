<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Filament\Resources\Groups\GroupResource;
use App\Models\Group;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class GroupsRelationManager extends RelationManager
{
    protected static string $relationship = 'groups';

    protected static ?string $title = 'المجموعات (لايف / أوفلاين)';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-user-group';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->type->value !== 'recorded';
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('المجموعة'),
                TextColumn::make('instructor.name')->label('المحاضر'),
                TextColumn::make('students_count')->label('الطلاب')->counts('students'),
                TextColumn::make('starts_on')->label('البداية')->date('d M Y'),
            ])
            ->headerActions([
                Action::make('new')->label('مجموعة جديدة')->icon('heroicon-o-plus')
                    ->url(fn () => GroupResource::getUrl('create', ['course' => $this->getOwnerRecord()->id]))
                    ->visible(fn () => auth()->user()->can('create', Group::class)),
            ])
            ->recordActions([
                Action::make('open')->label('فتح')->icon('heroicon-o-arrow-left')->url(fn ($record) => GroupResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
