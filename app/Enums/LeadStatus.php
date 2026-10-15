<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum LeadStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case AssessmentScheduled = 'assessment_scheduled';
    case AssessmentCompleted = 'assessment_completed';
    case OnHold = 'on_hold';
    case Enrolled = 'enrolled';
    case DroppedOut = 'dropped_out';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'قيد الانتظار',
            self::AssessmentScheduled => 'تم تحديد اختبار المستوى',
            self::AssessmentCompleted => 'تم اختبار المستوى',
            self::OnHold => 'معلق مؤقتاً',
            self::Enrolled => 'مسجل رسمياً',
            self::DroppedOut => 'منسحب / غير مشترك',
        };
    }

    public function englishLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::AssessmentScheduled => 'Assessment Scheduled',
            self::AssessmentCompleted => 'Assessment Completed',
            self::OnHold => 'On Hold',
            self::Enrolled => 'Enrolled',
            self::DroppedOut => 'Dropped Out',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::AssessmentScheduled => 'info',
            self::AssessmentCompleted => 'primary',
            self::OnHold => 'gray',
            self::Enrolled => 'success',
            self::DroppedOut => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pending => 'heroicon-o-clock',
            self::AssessmentScheduled => 'heroicon-o-calendar-days',
            self::AssessmentCompleted => 'heroicon-o-clipboard-document-check',
            self::OnHold => 'heroicon-o-pause-circle',
            self::Enrolled => 'heroicon-o-check-badge',
            self::DroppedOut => 'heroicon-o-x-circle',
        };
    }

    /** Hex used by the kanban board column accents. */
    public function hex(): string
    {
        return match ($this) {
            self::Pending => '#D97706',
            self::AssessmentScheduled => '#33B8F6',
            self::AssessmentCompleted => '#4F46E5',
            self::OnHold => '#64748B',
            self::Enrolled => '#16A34A',
            self::DroppedOut => '#DC2626',
        };
    }

    /** The SLA clock only matters until someone has made contact. */
    public function stopsSla(): bool
    {
        return $this !== self::Pending;
    }
}
