<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Video\BunnyStream;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        return view('site.courses.index');
    }

    public function show(Course $course, BunnyStream $bunny): View
    {
        abort_unless($course->is_published || auth()->user()?->can('update', $course), 404);

        $course->load([
            'department',
            'instructor.instructorProfile',
            'sections.lessons',
            'groups' => fn ($q) => $q->where('status', 'active')->withCount('students')->with(['sessions' => fn ($s) => $s->where('starts_at', '>=', now())->orderBy('starts_at')->limit(3)]),
            'reviews' => fn ($q) => $q->with('user')->latest()->limit(6),
        ]);

        $preview = $course->lessons()->where('is_preview', true)->first();

        return view('site.courses.show', [
            'course' => $course,
            'previewUrl' => $bunny->embedUrl($preview?->video_id),
            'preview' => $preview,
            'enrolled' => auth()->user()?->isEnrolledIn($course) ?? false,
            'rating' => round((float) $course->reviews()->avg('rating'), 1),
            'reviewsCount' => $course->reviews()->count(),
            'related' => Course::published()->where('department_id', $course->department_id)->whereKeyNot($course->id)->with(['department', 'instructor'])->take(3)->get(),
        ]);
    }
}
