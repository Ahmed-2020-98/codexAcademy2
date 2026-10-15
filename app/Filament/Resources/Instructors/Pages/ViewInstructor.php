<?php

namespace App\Filament\Resources\Instructors\Pages;

use App\Filament\Resources\Instructors\InstructorResource;
use App\Filament\Resources\Instructors\Widgets\InstructorKpiStats;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInstructor extends ViewRecord
{
    protected static string $resource = InstructorResource::class;

    public function getTitle(): string
    {
        return $this->record->user->name;
    }

    protected function getHeaderActions(): array
    {
        return [EditAction::make()->label('تعديل الملف التعريفي')];
    }

    protected function getHeaderWidgets(): array
    {
        return [InstructorKpiStats::class];
    }
}
