<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UpskillingStatus: string implements HasColor, HasLabel
{
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Done = 'done';

    public function getLabel(): string
    {
        return match ($this) {
            self::Assigned => 'مُسندة',
            self::InProgress => 'قيد التنفيذ',
            self::Done => 'منجزة',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Assigned => 'gray',
            self::InProgress => 'warning',
            self::Done => 'success',
        };
    }
}
