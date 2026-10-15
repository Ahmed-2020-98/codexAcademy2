<?php

namespace Tests\Feature;

use App\Enums\CourseType;
use App\Enums\Role;
use App\Livewire\Checkout;
use App\Livewire\MyTasks;
use App\Models\Group;
use App\Models\Payment;
use App\Models\ReferralCode;
use App\Models\StudentTask;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Services\Learning\ProgressService;
use App\Services\Tasks\TaskTemplateAssigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LearningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_certificate_is_issued_after_all_lessons_and_passing_the_quiz(): void
    {
        $student = $this->userWithRole(Role::Student);
        $course = $this->makeCourse();
        $lessons = collect(range(1, 3))->map(fn ($i) => $course->lessons()->create(['title' => "L{$i}", 'sort' => $i]));
        $quiz = $course->quiz()->create(['title' => 'Final', 'passing_score' => 60]);
        $q = $quiz->questions()->create(['question' => 'Q', 'options' => ['a', 'b'], 'correct_index' => 1]);
        $student->enrollments()->create(['course_id' => $course->id]);

        $progress = app(ProgressService::class);
        $lessons->each(fn ($lesson) => $progress->completeLesson($student, $lesson));

        $this->assertSame(100, $student->enrollments()->first()->progress);
        $this->assertSame(0, $student->certificates()->count(), 'No certificate before the quiz is passed.');

        $this->assertFalse($progress->submitQuiz($student, $quiz, [$q->id => 0])->passed);
        $this->assertTrue($progress->submitQuiz($student, $quiz, [$q->id => 1])->passed);

        $this->assertSame(1, $student->certificates()->count());
        $this->get(route('certificates.show', $student->certificates()->first()))->assertOk()->assertSee($student->name);
    }

    public function test_checkout_applies_referral_discount_and_tracks_the_redemption(): void
    {
        $referrer = $this->userWithRole(Role::Student);
        $code = ReferralCode::issueFor($referrer);
        $buyer = $this->userWithRole(Role::Student);
        $course = $this->makeCourse(attributes: ['price' => 2000]);

        Livewire::actingAs($buyer)
            ->test(Checkout::class, ['course' => $course])
            ->set('code', $code->code)
            ->call('applyCode')
            ->assertHasNoErrors()
            ->call('pay')
            ->assertRedirect(route('learn.course', $course));

        $payment = Payment::sole();
        $this->assertEquals(200, $payment->discount_amount);
        $this->assertEquals(1800, $payment->amount);
        $this->assertSame(1, $code->fresh()->uses_count);
        $this->assertTrue($buyer->isEnrolledIn($course));
        $this->assertNotNull($buyer->fresh()->referralCode, 'Buyer receives their own code.');
    }

    public function test_student_cannot_use_their_own_referral_code(): void
    {
        $student = $this->userWithRole(Role::Student);
        $code = ReferralCode::issueFor($student);

        Livewire::actingAs($student)
            ->test(Checkout::class, ['course' => $this->makeCourse()])
            ->set('code', $code->code)
            ->call('applyCode')
            ->assertHasErrors('code');
    }

    public function test_task_template_is_assigned_to_a_cohort_of_500_students(): void
    {
        $course = $this->makeCourse(CourseType::Live);
        $group = Group::create(['course_id' => $course->id, 'name' => 'Big cohort', 'mode' => 'live']);
        $ids = User::factory()->count(500)->create()->pluck('id');
        $group->students()->attach($ids);

        $template = TaskTemplate::create(['name' => 'Plan']);
        foreach (range(1, 4) as $w) {
            $template->items()->create(['week_no' => $w, 'title' => "Task {$w}"]);
        }

        $created = app(TaskTemplateAssigner::class)->assignToGroup($template, $group);

        $this->assertSame(2000, $created);
        $this->assertSame(2000, StudentTask::where('group_id', $group->id)->count());
        $this->assertSame(4, StudentTask::where('user_id', $ids->first())->count());
    }

    public function test_student_ticks_tasks_and_cannot_touch_someone_elses(): void
    {
        $student = $this->userWithRole(Role::Student);
        $other = $this->userWithRole(Role::Student);
        $mine = StudentTask::create(['user_id' => $student->id, 'week_no' => 1, 'title' => 'Mine']);
        $theirs = StudentTask::create(['user_id' => $other->id, 'week_no' => 1, 'title' => 'Theirs']);

        Livewire::actingAs($student)->test(MyTasks::class)
            ->assertSee('Mine')
            ->assertDontSee('Theirs')
            ->call('toggle', $mine->id)
            ->assertSee('100%');

        $this->assertNotNull($mine->fresh()->completed_at);

        Livewire::actingAs($student)->test(MyTasks::class)->call('toggle', $theirs->id)->assertForbidden();
    }

    public function test_lessons_are_locked_for_non_enrolled_students_except_previews(): void
    {
        $student = $this->userWithRole(Role::Student);
        $course = $this->makeCourse();
        $preview = $course->lessons()->create(['title' => 'Intro', 'is_preview' => true, 'sort' => 0]);
        $locked = $course->lessons()->create(['title' => 'Paid', 'sort' => 1]);

        $this->actingAs($student)->get(route('learn.lesson', [$course, $locked]))->assertForbidden();
        $this->actingAs($student)->get(route('learn.lesson', [$course, $preview]))->assertOk();
    }
}
