<?php

namespace App\Models;

use App\Enums\UpskillingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UpskillingTask extends Model
{
    protected $guarded = [];

    protected $casts = [
        'month' => 'date',
        'due_on' => 'date',
        'completed_at' => 'datetime',
        'status' => UpskillingStatus::class,
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
