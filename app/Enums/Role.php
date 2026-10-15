<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Role: string implements HasLabel
{
    case SuperAdmin = 'super_admin';
    case Sales = 'sales';
    case Instructor = 'instructor';
    case Student = 'student';

    public function getLabel(): string
    {
        return match ($this) {
            self::SuperAdmin => 'مدير النظام',
            self::Sales => 'المبيعات / خدمة العملاء',
            self::Instructor => 'محاضر',
            self::Student => 'طالب',
        };
    }

    /** Roles allowed into the Filament back office. */
    public static function staff(): array
    {
        return [self::SuperAdmin->value, self::Sales->value, self::Instructor->value];
    }
}
