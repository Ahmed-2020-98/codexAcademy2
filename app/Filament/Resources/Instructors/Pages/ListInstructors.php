<?php

namespace App\Filament\Resources\Instructors\Pages;

use App\Filament\Resources\Instructors\InstructorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInstructors extends ListRecords
{
    protected static string $resource = InstructorResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('إضافة محاضر')];
    }

    public function mount(): void
    {
        parent::mount();

        // Instructors land straight on their own profile.
        $user = auth()->user();
        if (! $user->isSuperAdmin() && $user->instructorProfile) {
            $this->redirect(InstructorResource::getUrl('view', ['record' => $user->instructorProfile]));
        }
    }
}
