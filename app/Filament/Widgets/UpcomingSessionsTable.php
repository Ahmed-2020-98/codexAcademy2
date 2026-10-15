<?php

namespace App\Filament\Widgets;

use App\Enums\SessionStatus;
use App\Filament\Resources\Groups\GroupResource;
use App\Models\GroupSession;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingSessionsTable extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->isSuperAdmin() || $user?->isInstructor();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('المحاضرات القادمة')
            ->query(fn () => GroupSession::query()
                ->with('group.course', 'group.instructor')
                ->where('status', SessionStatus::Scheduled)
                ->where('starts_at', '>=', now())
                ->when(! auth()->user()->isSuperAdmin(), fn ($q) => $q->whereHas('group', fn ($g) => $g->where('instructor_id', auth()->id())))
                ->orderBy('starts_at'))
            ->paginated([5])
            ->columns([
                TextColumn::make('starts_at')->label('الموعد')->dateTime('D d M — h:i A'),
                TextColumn::make('title')->label('الجلسة'),
                TextColumn::make('group.name')->label('المجموعة')->limit(34),
                TextColumn::make('group.mode')->label('النمط')->badge(),
                TextColumn::make('group.instructor.name')->label('المحاضر')->visible(fn () => auth()->user()->isSuperAdmin()),
            ])
            ->recordUrl(fn (GroupSession $r) => GroupResource::getUrl('view', ['record' => $r->group_id]));
    }
}
