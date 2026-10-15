<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstructorProfile extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'specialties' => 'array',
        'qualifications' => 'array',
        'hourly_rate' => 'decimal:2',
        'rating' => 'decimal:1',
        'is_published' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(InstructorAttendanceLog::class, 'instructor_id', 'user_id');
    }

    public function upskillingTasks(): HasMany
    {
        return $this->hasMany(UpskillingTask::class, 'instructor_id', 'user_id');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'instructor_id', 'user_id');
    }
}
