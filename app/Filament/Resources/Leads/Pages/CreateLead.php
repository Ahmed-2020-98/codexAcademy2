<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Enums\LeadSource;
use App\Filament\Resources\Leads\LeadResource;
use App\Services\Crm\LeadPipeline;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;

    /** Manual registrations go through the same intake as Google Forms (SLA + WhatsApp). */
    protected function handleRecordCreation(array $data): Model
    {
        $source = LeadSource::tryFrom($data['source'] ?? '') ?? LeadSource::Manual;
        unset($data['source']);

        return app(LeadPipeline::class)->intake($data, $source, auth()->user());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
