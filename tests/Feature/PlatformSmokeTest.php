<?php

namespace Tests\Feature;

use App\Filament\Widgets\AcademyStats;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Group;
use App\Models\InstructorProfile;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Renders every page against the full demo dataset. */
class PlatformSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The in-memory schema is shared across test classes, so seed explicitly (rolled back after each test).
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_pages_render(): void
    {
        $paths = [
            '/', '/courses', '/courses?type=live', '/instructors', '/register', '/login', '/signup',
            '/courses/'.Course::first()->slug,
            '/courses/'.Course::where('type', 'live')->first()->slug,
            '/instructors/'.InstructorProfile::first()->slug,
            '/certificates/'.Certificate::first()->serial,
        ];

        foreach ($paths as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_super_admin_back_office_renders(): void
    {
        $admin = User::where('email', 'admin@codex.academy')->first();
        $lead = Lead::first();
        $group = Group::first();
        $course = Course::where('type', 'recorded')->first();
        $profile = InstructorProfile::first();
        $student = User::where('email', 'student@codex.academy')->first();

        $paths = [
            '/admin', '/admin/pipeline-board', '/admin/leads', "/admin/leads/{$lead->id}", "/admin/leads/{$lead->id}/edit", '/admin/leads/create',
            '/admin/dropout-report', '/admin/courses', "/admin/courses/{$course->slug}/edit", '/admin/groups', "/admin/groups/{$group->id}",
            '/admin/students', "/admin/students/{$student->id}", '/admin/task-templates', '/admin/referral-codes',
            '/admin/instructors', "/admin/instructors/{$profile->slug}", '/admin/profit-loss', '/admin/payments', '/admin/expenses',
            '/admin/instructor-payables', '/admin/departments', '/admin/dropout-reasons', '/admin/expense-categories', '/admin/staff',
            '/admin/settings', '/admin/whatsapp-messages', '/admin/reports/pl/print',
        ];

        foreach ($paths as $path) {
            $this->actingAs($admin)->get($path)->assertOk();
        }
    }

    public function test_sales_and_instructor_dashboards_render(): void
    {
        $sales = User::where('email', 'sales@codex.academy')->first();
        $instructor = User::where('email', 'instructor1@codex.academy')->first();

        $this->actingAs($sales)->get('/admin')->assertOk();
        Livewire::actingAs($sales)->test(AcademyStats::class)->assertSee('طلابي قيد الانتظار')->assertDontSee('إيرادات الشهر');

        $this->actingAs($instructor)->get('/admin')->assertOk();
        Livewire::actingAs($instructor)->test(AcademyStats::class)->assertSee('مجموعاتي')->assertDontSee('صافي ربح الشهر');
    }

    public function test_student_portal_renders(): void
    {
        $student = User::where('email', 'student@codex.academy')->first();
        $recorded = $student->enrollments()->whereHas('course', fn ($q) => $q->where('type', 'recorded'))->first()->course;
        $live = $student->enrollments()->whereHas('course', fn ($q) => $q->where('type', 'live'))->first()->course;
        $notOwned = Course::where('type', 'recorded')->whereNotIn('id', $student->enrollments()->pluck('course_id'))->first();

        $this->actingAs($student);
        foreach (['/learn', '/learn/courses', '/learn/tasks', '/learn/certificates', "/learn/courses/{$live->slug}", "/learn/checkout/{$notOwned->slug}"] as $path) {
            $this->get($path)->assertOk();
        }

        $this->get("/learn/courses/{$recorded->slug}")->assertRedirect();
        $this->followingRedirects()->get("/learn/courses/{$recorded->slug}")->assertOk()->assertSee('cx-watermark', false);
        $this->get("/learn/courses/{$recorded->slug}/quiz")->assertOk();
    }
}
