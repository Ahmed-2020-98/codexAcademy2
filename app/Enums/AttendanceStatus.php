<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AttendanceStatus: string implements HasColor, HasLabel
{
    case Present = 'present';
    case Late = 'late';
    case Absent = 'absent';
    case Excused = 'excused';

    public function getLabel(): string
    {
        return match ($this) {
            self::Present => 'حاضر',
            self::Late => 'متأخر',
            self::Absent => 'غائب',
            self::Excused => 'معتذر',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Present => 'success',
            self::Late => 'warning',
            self::Absent => 'danger',
            self::Excused => 'gray',
        };
    }
}
