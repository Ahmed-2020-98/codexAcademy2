<?php

namespace App\Livewire;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Setting;
use App\Services\Learning\ProgressService;
use App\Services\Video\BunnyStream;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.learn')]
class LessonPlayer extends Component
{
    public Course $course;

    public Lesson $lesson;

    public function mount(Course $course, Lesson $lesson): void
    {
        abort_unless($lesson->course_id === $course->id, 404);

        $user = auth()->user();
        abort_unless($lesson->is_preview || $user->isEnrolledIn($course) || $user->can('update', $course), 403, 'هذا الدرس متاح للمشتركين فقط.');
    }

    public function complete(ProgressService $progress): void
    {
        $enrollment = $progress->completeLesson(auth()->user(), $this->lesson);

        if ($next = $this->nextLesson()) {
            $this->redirectRoute('learn.lesson', [$this->course, $next], navigate: false);

            return;
        }

        $this->dispatch('lesson-completed', progress: $enrollment->progress);
    }

    protected function nextLesson(): ?Lesson
    {
        return $this->course->lessons()->where('sort', '>', $this->lesson->sort)->first();
    }

    public function render(BunnyStream $bunny)
    {
        $user = auth()->user();
        $done = $user->lessonProgress()->whereNotNull('completed_at')->pluck('lesson_id')->all();
        $enrollment = $user->enrollments()->where('course_id', $this->course->id)->first();

        return view('livewire.lesson-player', [
            'sections' => $this->course->sections()->with('lessons')->get(),
            'done' => $done,
            'embedUrl' => $bunny->embedUrl($this->lesson->video_id),
            'enrollment' => $enrollment,
            'next' => $this->nextLesson(),
            'quiz' => $this->course->quiz,
            'passed' => $this->course->quiz?->attempts()->where('user_id', $user->id)->where('passed', true)->exists(),
            'certificate' => $user->certificates()->where('course_id', $this->course->id)->first(),
            'watermark' => [
                'name' => $user->name,
                'phone' => $user->phone,
                'id' => 'ID '.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT),
                'interval' => (int) Setting::get('watermark_interval_seconds') * 1000,
            ],
        ])->title($this->lesson->title);
    }
}
