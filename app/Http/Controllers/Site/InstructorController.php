<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\InstructorProfile;
use App\Models\Review;
use App\Services\Video\BunnyStream;
use Illuminate\View\View;

class InstructorController extends Controller
{
    public function index(): View
    {
        return view('site.instructors.index', [
            'instructors' => InstructorProfile::where('is_published', true)->with('user', 'department')->withCount('courses')->orderByDesc('rating')->get(),
        ]);
    }

    public function show(InstructorProfile $instructor, BunnyStream $bunny): View
    {
        abort_unless($instructor->is_published, 404);
        $instructor->load('user', 'department');

        return view('site.instructors.show', [
            'profile' => $instructor,
            'introUrl' => $bunny->embedUrl($instructor->intro_video_id),
            'demoUrl' => $bunny->embedUrl($instructor->demo_video_id),
            'courses' => $instructor->courses()->published()->with('department', 'instructor')->get(),
            'reviews' => Review::where('instructor_id', $instructor->user_id)->with('user', 'course')->latest()->take(6)->get(),
        ]);
    }
}
