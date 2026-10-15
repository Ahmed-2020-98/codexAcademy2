<?php

namespace App\Filament\Resources\DropoutReasons\Pages;

use App\Filament\Resources\DropoutReasons\DropoutReasonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDropoutReasons extends ManageRecords
{
    protected static string $resource = DropoutReasonResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
