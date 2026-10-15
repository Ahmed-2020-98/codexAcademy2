<?php

namespace App\Services\Instructors;

use App\Enums\SessionStatus;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\GroupSession;
use App\Models\User;

class InstructorKpis
{
    /** @return array{hours: float, sessions: int, students: int, avg_progress: int, attendance_rate: int, absences: int} */
    public function for(User $instructor): array
    {
        $sessions = GroupSession::query()
            ->whereHas('group', fn ($q) => $q->where('instructor_id', $instructor->id))
            ->where('status', SessionStatus::Completed);

        $groupIds = $instructor->taughtGroups()->pluck('id');
        $courseIds = $instructor->taughtCourses()->pluck('id');

        $students = Enrollment::query()
            ->whereIn('course_id', $courseIds)
            ->distinct('user_id')
            ->count('user_id');

        $avgProgress = (int) round((float) Enrollment::query()->whereIn('course_id', $courseIds)->avg('progress'));

        $attendance = Attendance::query()->whereHas('session', fn ($q) => $q->whereIn('group_id', $groupIds));
        $total = (clone $attendance)->count();
        $present = (clone $attendance)->whereIn('status', ['present', 'late'])->count();

        return [
            'hours' => (float) (clone $sessions)->sum('duration_hours'),
            'sessions' => (clone $sessions)->count(),
            'students' => $students,
            'avg_progress' => $avgProgress,
            'attendance_rate' => $total ? (int) round($present / $total * 100) : 0,
            'absences' => $instructor->instructorAttendanceLogs()->count(),
        ];
    }
}
