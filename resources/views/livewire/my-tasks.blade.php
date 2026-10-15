<div>
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold sm:text-3xl">مهامي</h1>
            <p class="mt-2 text-muted">قائمتك الأسبوعية من المحاضر. علّم المهمة عند إنجازها ليتحدث شريط تقدمك تلقائياً.</p>
        </div>
        <div class="card w-full p-4 sm:w-72">
            <div class="mb-2 flex justify-between text-sm"><span class="text-muted">الإنجاز الكلي</span><span class="font-mono font-semibold">{{ $overall }}%</span></div>
            <x-pixel-progress :value="$overall" :segments="14" label="الإنجاز الكلي" />
        </div>
    </header>

    @if ($weeks->isEmpty())
        <div class="card mt-8 grid place-items-center gap-3 px-6 py-16 text-center">
            <x-heroicon-o-check-circle class="h-10 w-10 text-sky" />
            <p class="font-semibold">لا توجد مهام بعد</p>
            <p class="text-sm text-muted">ستظهر هنا خطتك الأسبوعية بمجرد أن يسندها المحاضر لمجموعتك.</p>
        </div>
    @else
        <nav class="mt-8 flex gap-2 overflow-x-auto pb-2" aria-label="الأسابيع">
            @foreach ($weeks as $no => $w)
                <button type="button" wire:click="$set('week', {{ $no }})" aria-pressed="{{ $week == $no ? 'true' : 'false' }}"
                        @class(['flex shrink-0 flex-col items-start gap-1.5 rounded-xl px-4 py-3 text-start transition-colors', 'bg-navy text-white shadow-[3px_3px_0_var(--cx-sky)]' => $week == $no, 'bg-white text-ink ring-1 ring-line hover:ring-navy/40' => $week != $no])>
                    <span class="text-sm font-semibold">الأسبوع {{ $no }}</span>
                    <span @class(['font-mono text-xs', 'text-white/75' => $week == $no, 'text-muted' => $week != $no])>{{ $w['done'] }}/{{ $w['total'] }} {{ $w['done'] === $w['total'] ? '✓' : '' }}</span>
                </button>
            @endforeach
        </nav>

        <section class="mt-6 grid gap-6 lg:grid-cols-[1fr_18rem]" aria-labelledby="week-heading">
            <div>
                <h2 id="week-heading" class="sr-only">مهام الأسبوع {{ $week }}</h2>
                <ul class="grid gap-3">
                    @foreach ($tasks as $task)
                        <li wire:key="task-{{ $task->id }}">
                            <label @class(['cx-task', 'is-done' => $task->completed_at])>
                                <input type="checkbox" @checked($task->completed_at) wire:click="toggle({{ $task->id }})" aria-describedby="task-meta-{{ $task->id }}">
                                <span class="min-w-0 flex-1">
                                    <span class="cx-task__title block font-medium">{{ $task->title }}</span>
                                    <span id="task-meta-{{ $task->id }}" class="mt-0.5 block text-xs text-muted">
                                        {{ $task->group?->name }}
                                        @if ($task->due_on) · التسليم {{ $task->due_on->translatedFormat('l d M') }} @endif
                                    </span>
                                </span>
                                @if (! $task->completed_at && $task->due_on?->isPast())
                                    <span class="chip chip-red">متأخرة</span>
                                @endif
                            </label>
                        </li>
                    @endforeach
                </ul>
            </div>
            <aside class="card h-fit p-6 text-center">
                <p class="text-sm text-muted">إنجاز الأسبوع {{ $week }}</p>
                <p class="mt-2 font-mono text-5xl font-semibold text-navy" aria-live="polite">{{ $weekPct }}%</p>
                <x-pixel-progress :value="$weekPct" class="mt-5" label="إنجاز الأسبوع" />
                <p class="mt-5 text-sm text-muted">{{ $weekPct === 100 ? 'أسبوع مكتمل! استمر بنفس الحماس 🔥' : 'خطوة صغيرة كل يوم تصنع الفرق.' }}</p>
            </aside>
        </section>
    @endif
</div>
