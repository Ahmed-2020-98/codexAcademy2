<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar_path',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->hasAnyRole(Role::staff());
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::SuperAdmin->value);
    }

    public function isSales(): bool
    {
        return $this->hasRole(Role::Sales->value);
    }

    public function isInstructor(): bool
    {
        return $this->hasRole(Role::Instructor->value);
    }

    public function isStudent(): bool
    {
        return $this->hasRole(Role::Student->value);
    }

    public function initials(): string
    {
        // Skip honorifics such as "أ." or "Dr." so the avatar shows the actual name.
        return Str::of($this->name)->explode(' ')
            ->reject(fn ($part) => $part === '' || str_ends_with($part, '.'))
            ->take(2)
            ->map(fn ($part) => mb_substr($part, 0, 1))
            ->implode("\u{200C}"); // zero-width non-joiner keeps Arabic initials as separate letters
    }

    // ---- Instructor side -------------------------------------------------

    public function instructorProfile(): HasOne
    {
        return $this->hasOne(InstructorProfile::class);
    }

    public function taughtCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    public function taughtGroups(): HasMany
    {
        return $this->hasMany(Group::class, 'instructor_id');
    }

    public function instructorAttendanceLogs(): HasMany
    {
        return $this->hasMany(InstructorAttendanceLog::class, 'instructor_id');
    }

    public function upskillingTasks(): HasMany
    {
        return $this->hasMany(UpskillingTask::class, 'instructor_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(InstructorPayout::class, 'instructor_id');
    }

    // ---- Student side ----------------------------------------------------

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'enrollments')->withPivot(['progress', 'status', 'group_id'])->withTimestamps();
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_student')->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(StudentTask::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function referralCode(): HasOne
    {
        return $this->hasOne(ReferralCode::class);
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function isEnrolledIn(Course $course): bool
    {
        return $this->enrollments()->where('course_id', $course->id)->where('status', '!=', 'cancelled')->exists();
    }
}
