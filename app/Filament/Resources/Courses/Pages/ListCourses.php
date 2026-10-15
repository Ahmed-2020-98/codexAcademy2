<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Enums\CourseType;
use App\Filament\Resources\Courses\CourseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCourses extends ListRecords
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        $tabs = ['all' => Tab::make('الكل')];

        foreach (CourseType::cases() as $type) {
            $tabs[$type->value] = Tab::make($type->getLabel())
                ->icon($type->getIcon())
                ->modifyQueryUsing(fn (Builder $q) => $q->where('type', $type));
        }

        return $tabs;
    }
}
