<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupSession extends Model
{
    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'duration_hours' => 'decimal:2',
        'status' => SessionStatus::class,
        'reminded_24h_at' => 'datetime',
        'reminded_1h_at' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function joinUrl(): ?string
    {
        return $this->meeting_url ?: $this->group?->meeting_url;
    }
}
