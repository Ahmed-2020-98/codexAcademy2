<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('whatsapp')->label('واتساب')->icon('heroicon-o-chat-bubble-left-right')->color('success')
                ->url(fn () => 'https://wa.me/'.ltrim((string) $this->record->phone, '+'), shouldOpenInNewTab: true),
        ];
    }
}
