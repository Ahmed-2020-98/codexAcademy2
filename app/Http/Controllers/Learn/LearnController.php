<?php

namespace App\Http\Controllers\Learn;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\GroupSession;
use App\Models\StudentTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LearnController extends Controller
{
    public function dashboard(): View
    {
        $user = auth()->user();
        $enrollments = $user->enrollments()->with('course.instructor', 'course.department')->where('status', '!=', 'cancelled')->latest('updated_at')->get();

        $weekTasks = StudentTask::where('user_id', $user->id)
            ->whereBetween('due_on', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])
            ->get();

        return view('learn.dashboard', [
            'user' => $user,
            'enrollments' => $enrollments,
            'continue' => $enrollments->firstWhere(fn ($e) => $e->course->type->value === 'recorded' && $e->progress < 100),
            'weekTasks' => $weekTasks,
            'sessions' => GroupSession::query()
                ->whereIn('group_id', $user->groups()->pluck('groups.id'))
                ->where('status', SessionStatus::Scheduled)
                ->where('starts_at', '>=', now()->subHours(2))
                ->with('group.course')
                ->orderBy('starts_at')
                ->take(4)
                ->get(),
            'certificates' => $user->certificates()->count(),
        ]);
    }

    public function courses(): View
    {
        return view('learn.courses', [
            'enrollments' => auth()->user()->enrollments()->with('course.instructor', 'course.department', 'group')->latest('enrolled_at')->get(),
        ]);
    }

    /** Recorded courses open the player; live/offline courses open the cohort page. */
    public function course(Course $course): View|RedirectResponse
    {
        $user = auth()->user();
        $enrollment = $user->enrollments()->where('course_id', $course->id)->first();
        abort_unless($enrollment || $user->can('update', $course), 403, 'هذا المحتوى متاح للمشتركين فقط.');

        if (! $course->type->isInteractive()) {
            $done = $user->lessonProgress()->whereNotNull('completed_at')->pluck('lesson_id');
            $next = $course->lessons()->whereNotIn('id', $done)->first() ?? $course->lessons()->first();

            return $next ? redirect()->route('learn.lesson', [$course, $next]) : redirect()->route('learn.courses');
        }

        $group = $enrollment?->group ?? $user->groups()->where('course_id', $course->id)->first();
        $group?->load(['sessions', 'instructor']);

        $attendance = $group
            ? $user->hasMany(Attendance::class)->whereIn('group_session_id', $group->sessions->pluck('id'))->get()->keyBy('group_session_id')
            : collect();

        return view('learn.cohort', [
            'course' => $course,
            'group' => $group,
            'attendance' => $attendance,
            'grades' => $group ? $group->grades()->where('user_id', $user->id)->latest()->get() : collect(),
        ]);
    }

    public function certificates(): View
    {
        return view('learn.certificates', [
            'certificates' => auth()->user()->certificates()->with('course.department')->latest('issued_at')->get(),
        ]);
    }
}
