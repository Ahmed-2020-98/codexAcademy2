<?php

namespace App\Models;

use App\Enums\CourseType;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'status' => LeadStatus::class,
        'source' => LeadSource::class,
        'study_mode' => CourseType::class,
        'sla_due_at' => 'datetime',
        'is_overdue' => 'boolean',
        'overdue_notified_at' => 'datetime',
        'first_contacted_at' => 'datetime',
        'assessment_at' => 'datetime',
        'dropout_approved_at' => 'datetime',
        'payload' => 'array',
    ];

    protected static function booted(): void
    {
        // The 24H SLA timer starts the moment the registration lands.
        static::creating(function (Lead $lead): void {
            $lead->status ??= LeadStatus::Pending;
            $lead->sla_due_at ??= now()->addHours((int) Setting::get('sla_hours'));
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function dropoutReason(): BelongsTo
    {
        return $this->belongsTo(DropoutReason::class);
    }

    public function dropoutApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dropout_approved_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest();
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('is_overdue', true)->where('status', LeadStatus::Pending);
    }

    /** Pending leads whose SLA window has elapsed but are not flagged yet. */
    public function scopeBreachingSla(Builder $query): Builder
    {
        return $query->where('status', LeadStatus::Pending)
            ->where('is_overdue', false)
            ->where('sla_due_at', '<=', now());
    }

    public function slaSecondsLeft(): ?int
    {
        if ($this->status !== LeadStatus::Pending || ! $this->sla_due_at) {
            return null;
        }

        return (int) now()->diffInSeconds($this->sla_due_at, false);
    }
}
