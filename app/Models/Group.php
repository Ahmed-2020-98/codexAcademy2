<?php

namespace App\Models;

use App\Enums\CourseType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'mode' => CourseType::class,
        'starts_on' => 'date',
        'ends_on' => 'date',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_student')->withTimestamps();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(GroupSession::class)->orderBy('starts_at');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(StudentTask::class);
    }
}
