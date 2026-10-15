<?php

namespace App\Livewire;

use App\Models\Course;
use App\Models\QuizAttempt;
use App\Services\Learning\ProgressService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.learn')]
class TakeQuiz extends Component
{
    public Course $course;

    /** @var array<int, int|string> */
    public array $answers = [];

    public ?int $attemptId = null;

    public function mount(Course $course): void
    {
        abort_unless(auth()->user()->isEnrolledIn($course), 403);
        abort_unless($course->quiz, 404);
    }

    public function submit(ProgressService $progress): void
    {
        $questions = $this->course->quiz->questions;

        $this->validate(
            $questions->mapWithKeys(fn ($q) => ["answers.{$q->id}" => 'required|integer|min:0|max:'.(count($q->options) - 1)])->all(),
            ['answers.*.required' => 'اختر إجابة لهذا السؤال.'],
        );

        $this->attemptId = $progress->submitQuiz(auth()->user(), $this->course->quiz, array_map('intval', $this->answers))->id;
    }

    public function retry(): void
    {
        $this->reset('answers', 'attemptId');
    }

    public function render()
    {
        $user = auth()->user();
        $enrollment = $user->enrollments()->where('course_id', $this->course->id)->first();

        return view('livewire.take-quiz', [
            'quiz' => $this->course->quiz->load('questions'),
            'attempt' => $this->attemptId ? QuizAttempt::find($this->attemptId) : null,
            'best' => $this->course->quiz->attempts()->where('user_id', $user->id)->max('score'),
            'progress' => $enrollment?->progress ?? 0,
            'certificate' => $user->certificates()->where('course_id', $this->course->id)->first(),
        ])->title('الاختبار النهائي');
    }
}
