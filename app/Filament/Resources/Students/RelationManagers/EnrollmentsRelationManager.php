<?php

namespace App\Filament\Resources\Students\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'الكورسات المشترك بها';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-book-open';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('course.title')->label('الكورس')->weight('medium'),
                TextColumn::make('course.type')->label('النوع')->badge(),
                TextColumn::make('group.name')->label('المجموعة')->placeholder('—'),
                TextColumn::make('progress')->label('التقدم')->suffix('%')->extraAttributes(['class' => 'cx-mono']),
                TextColumn::make('status')->label('الحالة')->badge()->formatStateUsing(fn ($state) => ['active' => 'نشط', 'completed' => 'مكتمل', 'cancelled' => 'ملغي'][$state] ?? $state)
                    ->color(fn ($state) => ['active' => 'info', 'completed' => 'success', 'cancelled' => 'danger'][$state] ?? 'gray'),
                TextColumn::make('enrolled_at')->label('تاريخ الاشتراك')->date('d M Y'),
            ]);
    }
}
