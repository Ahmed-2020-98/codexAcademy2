<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Enums\LeadStatus;
use App\Filament\Pages\PipelineBoard;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('board')->label('لوحة المراحل')->icon('heroicon-o-view-columns')->color('gray')->url(PipelineBoard::getUrl()),
            CreateAction::make()->label('تسجيل طالب يدوياً'),
        ];
    }

    public function getTabs(): array
    {
        $counts = Lead::query()->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        $tabs = [
            'all' => Tab::make('الكل')->badge($counts->sum()),
            'overdue' => Tab::make('المتأخرون')
                ->icon('heroicon-o-bell-alert')
                ->badge(Lead::overdue()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $q) => $q->overdue()),
        ];

        foreach (LeadStatus::cases() as $status) {
            $tabs[$status->value] = Tab::make($status->getLabel())
                ->badge($counts[$status->value] ?? 0)
                ->badgeColor($status->getColor())
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', $status));
        }

        return $tabs;
    }
}
