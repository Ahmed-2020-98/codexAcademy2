<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasColor, HasLabel
{
    case Paid = 'paid';
    case Pending = 'pending';
    case Refunded = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::Paid => 'مدفوع',
            self::Pending => 'بانتظار الاعتماد',
            self::Refunded => 'مسترد',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Paid => 'success',
            self::Pending => 'warning',
            self::Refunded => 'danger',
        };
    }
}
