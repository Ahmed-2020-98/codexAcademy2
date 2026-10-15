<?php

namespace App\Services\Learning;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Str;

class ProgressService
{
    public function completeLesson(User $user, Lesson $lesson): Enrollment
    {
        $user->lessonProgress()->updateOrCreate(['lesson_id' => $lesson->id], ['completed_at' => now()]);

        return $this->recalculate($user, $lesson->course);
    }

    public function recalculate(User $user, Course $course): Enrollment
    {
        $enrollment = $user->enrollments()->where('course_id', $course->id)->firstOrFail();

        $total = $course->lessons()->count();
        $done = $user->lessonProgress()
            ->whereNotNull('completed_at')
            ->whereIn('lesson_id', $course->lessons()->select('id'))
            ->count();

        $enrollment->progress = $total > 0 ? (int) floor($done / $total * 100) : 0;
        $enrollment->save();

        $this->issueCertificateIfEligible($user, $course, $enrollment);

        return $enrollment;
    }

    /** @param array<int, int> $answers question id => chosen option index */
    public function submitQuiz(User $user, Quiz $quiz, array $answers): QuizAttempt
    {
        $questions = $quiz->questions;
        $correct = $questions->filter(fn ($q) => isset($answers[$q->id]) && (int) $answers[$q->id] === $q->correct_index)->count();
        $score = $questions->count() > 0 ? (int) round($correct / $questions->count() * 100) : 0;

        $attempt = $quiz->attempts()->create([
            'user_id' => $user->id,
            'score' => $score,
            'passed' => $score >= $quiz->passing_score,
            'answers' => $answers,
        ]);

        $this->recalculate($user, $quiz->course);

        return $attempt;
    }

    /** Certificates are issued automatically: all lessons watched + final quiz passed. */
    public function issueCertificateIfEligible(User $user, Course $course, Enrollment $enrollment): ?Certificate
    {
        if ($enrollment->progress < 100) {
            return null;
        }

        $quiz = $course->quiz;
        if ($quiz && ! $quiz->attempts()->where('user_id', $user->id)->where('passed', true)->exists()) {
            return null;
        }

        $enrollment->forceFill(['status' => 'completed', 'completed_at' => $enrollment->completed_at ?? now()])->save();

        return Certificate::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['serial' => 'CDX-'.now()->format('Y').'-'.Str::upper(Str::random(8)), 'issued_at' => now()],
        );
    }
}
