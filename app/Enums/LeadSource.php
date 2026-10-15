<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LeadSource: string implements HasLabel
{
    case GoogleForms = 'google_forms';
    case Site = 'site';
    case Manual = 'manual';
    case Referral = 'referral';

    public function getLabel(): string
    {
        return match ($this) {
            self::GoogleForms => 'Google Forms',
            self::Site => 'الموقع',
            self::Manual => 'إدخال يدوي',
            self::Referral => 'إحالة',
        };
    }
}
