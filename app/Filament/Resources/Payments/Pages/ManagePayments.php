<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\PaymentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePayments extends ManageRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إدخال حجز يدوي')
                ->icon('heroicon-o-plus')
                // Manual bookings land as Pending until a Super Admin approves them.
                ->mutateDataUsing(fn (array $data) => $data + [
                    'source' => 'manual',
                    'recorded_by' => auth()->id(),
                    'status' => auth()->user()->isSuperAdmin() ? PaymentStatus::Paid : PaymentStatus::Pending,
                    'approved_at' => auth()->user()->isSuperAdmin() ? now() : null,
                    'approved_by' => auth()->user()->isSuperAdmin() ? auth()->id() : null,
                ]),
        ];
    }
}
