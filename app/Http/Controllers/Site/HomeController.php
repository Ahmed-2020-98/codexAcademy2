<?php

namespace App\Http\Controllers\Site;

use App\Enums\CourseType;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Department;
use App\Models\InstructorProfile;
use App\Models\Review;
use App\Models\User;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('site.home', [
            'featured' => Course::published()->with(['department', 'instructor'])->orderByDesc('is_featured')->latest()->take(6)->get(),
            'departments' => Department::active()->withCount(['courses' => fn ($q) => $q->published()])->get(),
            'instructors' => InstructorProfile::where('is_published', true)->with('user', 'department')->orderByDesc('rating')->take(4)->get(),
            'reviews' => Review::with('user', 'course')->where('is_published', true)->whereNotNull('comment')->latest()->take(3)->get(),
            'types' => CourseType::cases(),
            'typeCounts' => Course::published()->selectRaw('type, COUNT(*) as c')->groupBy('type')->pluck('c', 'type'),
            'stats' => [
                'students' => User::role(Role::Student->value)->count(),
                'courses' => Course::published()->count(),
                'certificates' => Certificate::count(),
                'instructors' => InstructorProfile::count(),
            ],
        ]);
    }
}
