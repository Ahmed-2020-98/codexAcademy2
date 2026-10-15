<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case Card = 'card';
    case Wallet = 'wallet';
    case Transfer = 'transfer';
    case Cash = 'cash';

    public function getLabel(): string
    {
        return match ($this) {
            self::Card => 'بطاقة إلكترونية',
            self::Wallet => 'محفظة إلكترونية',
            self::Transfer => 'تحويل بنكي',
            self::Cash => 'نقدي',
        };
    }
}
