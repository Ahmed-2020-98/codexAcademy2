<?php

namespace Tests\Feature;

use App\Enums\CourseType;
use App\Enums\Role;
use App\Models\Group;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Mirrors the RBAC matrix in section 8 of the requirements document. */
class RbacMatrixTest extends TestCase
{
    use RefreshDatabase;

    public static function matrix(): array
    {
        // path => [super_admin, sales, instructor]
        return [
            'P&L report' => ['/admin/profit-loss', 200, 403, 403],
            'Instructor payables' => ['/admin/instructor-payables', 200, 403, 403],
            'Expenses log' => ['/admin/expenses', 200, 403, 403],
            'Departments & settings' => ['/admin/departments', 200, 403, 403],
            'System settings' => ['/admin/settings', 200, 403, 403],
            'Staff & roles' => ['/admin/staff', 200, 403, 403],
            'Pipeline board' => ['/admin/pipeline-board', 200, 200, 403],
            'Leads (pipeline & SLA)' => ['/admin/leads', 200, 200, 403],
            'Dropout analysis' => ['/admin/dropout-report', 200, 200, 403],
            'Manual bookings' => ['/admin/payments', 200, 200, 403],
            'Groups, attendance & grades' => ['/admin/groups', 200, 403, 200],
            'Courses / content' => ['/admin/courses', 200, 403, 200],
            'Task templates' => ['/admin/task-templates', 200, 403, 200],
        ];
    }

    #[DataProvider('matrix')]
    public function test_back_office_access_follows_the_matrix(string $path, int $admin, int $sales, int $instructor): void
    {
        $this->seedRoles();

        $this->actingAs($this->userWithRole(Role::SuperAdmin))->get($path)->assertStatus($admin);
        $this->actingAs($this->userWithRole(Role::Sales))->get($path)->assertStatus($sales);
        $this->actingAs($this->userWithRole(Role::Instructor))->get($path)->assertStatus($instructor);
    }

    public function test_students_cannot_enter_the_back_office(): void
    {
        $this->seedRoles();

        $this->actingAs($this->userWithRole(Role::Student))->get('/admin')->assertForbidden();
    }

    public function test_instructor_only_sees_their_own_groups(): void
    {
        $this->seedRoles();
        $mine = $this->userWithRole(Role::Instructor);
        $other = $this->userWithRole(Role::Instructor);
        $course = $this->makeCourse(CourseType::Live);

        $ownGroup = Group::create(['course_id' => $course->id, 'instructor_id' => $mine->id, 'name' => 'Own group', 'mode' => 'live']);
        $foreign = Group::create(['course_id' => $course->id, 'instructor_id' => $other->id, 'name' => 'Foreign group', 'mode' => 'live']);

        $this->actingAs($mine)->get('/admin/groups')->assertSee('Own group')->assertDontSee('Foreign group');
        $this->actingAs($mine)->get("/admin/groups/{$ownGroup->id}")->assertOk();
        $this->actingAs($mine)->get("/admin/groups/{$foreign->id}")->assertNotFound();
    }

    public function test_instructor_profile_is_private_to_its_owner(): void
    {
        $this->seedRoles();
        $mine = $this->userWithRole(Role::Instructor);
        $other = $this->userWithRole(Role::Instructor);
        $own = $mine->instructorProfile()->create(['slug' => 'mine']);
        $foreign = $other->instructorProfile()->create(['slug' => 'other']);

        $this->actingAs($mine)->get('/admin/instructors')->assertRedirect("/admin/instructors/{$own->slug}");
        $this->actingAs($mine)->get("/admin/instructors/{$own->slug}/edit")->assertOk();
        $this->actingAs($mine)->get("/admin/instructors/{$foreign->slug}/edit")->assertNotFound();
    }

    public function test_sales_only_see_the_bookings_they_recorded(): void
    {
        $this->seedRoles();
        $sales = $this->userWithRole(Role::Sales);
        $colleague = $this->userWithRole(Role::Sales);
        $student = User::factory()->create(['name' => 'Student Z']);

        Payment::create(['user_id' => $student->id, 'channel' => 'live', 'gross_amount' => 1111, 'amount' => 1111, 'source' => 'manual', 'recorded_by' => $sales->id, 'reference' => 'MINE-REF', 'paid_at' => now()]);
        Payment::create(['channel' => 'recorded', 'gross_amount' => 2222, 'amount' => 2222, 'source' => 'manual', 'recorded_by' => $colleague->id, 'reference' => 'OTHER-REF', 'paid_at' => now()]);

        $this->actingAs($sales)->get('/admin/payments')->assertSee('1,111')->assertDontSee('2,222');
    }
}
