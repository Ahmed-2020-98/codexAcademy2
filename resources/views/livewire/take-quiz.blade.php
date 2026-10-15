<div class="mx-auto max-w-3xl">
    <a href="{{ route('learn.course', $course) }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-navy"><x-heroicon-m-arrow-right class="h-4 w-4" /> {{ $course->title }}</a>
    <h1 class="mt-4 text-2xl font-bold sm:text-3xl">{{ $quiz->title }}</h1>
    <p class="mt-2 text-muted">{{ $quiz->questions->count() }} أسئلة · درجة النجاح <span class="font-mono">{{ $quiz->passing_score }}%</span>@if ($best !== null) · أفضل نتيجة لك <span class="font-mono">{{ $best }}%</span>@endif</p>

    @if ($attempt)
        <div @class(['card mt-8 p-8 text-center', 'ring-2 ring-green-500/40' => $attempt->passed]) role="status">
            <p class="font-mono text-6xl font-semibold {{ $attempt->passed ? 'text-navy' : 'text-red-700' }}">{{ $attempt->score }}%</p>
            <h2 class="mt-4 text-xl font-semibold">{{ $attempt->passed ? 'نجحت في الاختبار 🎉' : 'لم تصل لدرجة النجاح هذه المرة' }}</h2>
            @if ($attempt->passed && $certificate)
                <p class="mt-2 text-muted">تم إصدار شهادتك تلقائياً.</p>
                <a href="{{ route('certificates.show', $certificate) }}" target="_blank" class="btn btn-primary mt-6">عرض الشهادة</a>
            @elseif ($attempt->passed)
                <p class="mt-2 text-muted">أكمل باقي الدروس (<span class="font-mono">{{ $progress }}%</span>) لتصدر شهادتك.</p>
                <a href="{{ route('learn.course', $course) }}" class="btn btn-primary mt-6">العودة للدروس</a>
            @else
                <p class="mt-2 text-muted">راجع الدروس ثم أعد المحاولة — لا يوجد حد لعدد المحاولات.</p>
                <button type="button" wire:click="retry" class="btn btn-primary mt-6">إعادة المحاولة</button>
            @endif
        </div>
    @else
        <form wire:submit="submit" class="mt-8 grid gap-5">
            @foreach ($quiz->questions as $i => $question)
                <fieldset class="card p-6" wire:key="q-{{ $question->id }}">
                    <legend class="sr-only">السؤال {{ $i + 1 }}</legend>
                    <p class="flex gap-3 font-semibold leading-7"><span class="cx-step-num h-8! w-8! shrink-0 text-xs shadow-none!">{{ $i + 1 }}</span>{{ $question->question }}</p>
                    <div class="mt-4 grid gap-2.5 sm:grid-cols-2">
                        @foreach ($question->options as $o => $option)
                            <label @class(['flex cursor-pointer items-center gap-3 rounded-xl border-[1.5px] px-4 py-3 transition-colors', 'border-navy bg-navy-50' => (string) ($answers[$question->id] ?? '') === (string) $o, 'border-line hover:border-navy/40' => (string) ($answers[$question->id] ?? '') !== (string) $o])>
                                <input type="radio" wire:model.live="answers.{{ $question->id }}" value="{{ $o }}" class="h-4 w-4 accent-[#0A5A9E]">
                                <span>{{ $option }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error("answers.{$question->id}")<p class="field-error mt-3">{{ $message }}</p>@enderror
                </fieldset>
            @endforeach
            <div class="flex items-center justify-between gap-4">
                <p class="text-sm text-muted">أجبت على <span class="font-mono">{{ count(array_filter($answers, fn ($a) => $a !== '' && $a !== null)) }}/{{ $quiz->questions->count() }}</span></p>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">تسليم الإجابات</button>
            </div>
        </form>
    @endif
</div>
