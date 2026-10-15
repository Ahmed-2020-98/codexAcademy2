<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Resources\Courses\CourseResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')->label('عرض في الموقع')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                ->url(fn () => route('courses.show', $this->record), shouldOpenInNewTab: true),
            DeleteAction::make(),
        ];
    }
}
