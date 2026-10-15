<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Leads\Actions\LeadActions;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class OverdueLeadsTable extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->isSuperAdmin() || $user?->isSales();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('قائمة المتأخرين — تجاوزوا مؤشر الـ SLA')
            ->query(fn () => Lead::query()->overdue()->with(['course', 'assignee'])
                ->when(auth()->user()->isSales(), fn ($q) => $q->where('assigned_to', auth()->id()))
                ->orderBy('sla_due_at'))
            ->paginated([5])
            ->emptyStateHeading('لا يوجد متأخرون 🎉')
            ->emptyStateDescription('تم التواصل مع جميع التسجيلات الجديدة داخل المهلة.')
            ->columns([
                TextColumn::make('name')->label('الطالب')->description(fn (Lead $r) => $r->phone)->weight('medium'),
                TextColumn::make('course.title')->label('الكورس')->limit(30),
                TextColumn::make('sla')->label('التأخير')->state(fn (Lead $r) => LeadResource::slaLabel($r))->badge()->color('danger'),
                TextColumn::make('assignee.name')->label('المسؤول'),
            ])
            ->recordActions([LeadActions::changeStatus(), LeadActions::whatsapp()])
            ->recordUrl(fn (Lead $r) => LeadResource::getUrl('view', ['record' => $r]));
    }
}
