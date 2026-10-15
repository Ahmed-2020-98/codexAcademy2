<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorAttendanceLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'status' => AttendanceStatus::class,
        'is_accepted' => 'boolean',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GroupSession::class, 'group_session_id');
    }
}
