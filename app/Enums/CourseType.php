<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/** Also used as the revenue channel on payments (recorded / live / offline). */
enum CourseType: string implements HasColor, HasIcon, HasLabel
{
    case Recorded = 'recorded';
    case Live = 'live';
    case Offline = 'offline';

    public function getLabel(): string
    {
        return match ($this) {
            self::Recorded => 'مسجل',
            self::Live => 'لايف',
            self::Offline => 'أوفلاين',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Recorded => 'primary',
            self::Live => 'info',
            self::Offline => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Recorded => 'heroicon-o-play-circle',
            self::Live => 'heroicon-o-video-camera',
            self::Offline => 'heroicon-o-building-office-2',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Recorded => 'شراء مباشر وبث آمن بعلامة مائية ديناميكية، اختبارات وشهادات تلقائية.',
            self::Live => 'جلسات تفاعلية عبر Zoom / Google Meet مع روابط ومجموعات واتساب.',
            self::Offline => 'حضور مباشر في القاعات المخصصة مع متابعة كاملة للحضور والدرجات.',
        };
    }

    public function isInteractive(): bool
    {
        return $this !== self::Recorded;
    }
}
