<x-layouts.site title="المحاضرون">
    <section class="cx-grid-bg border-b border-line">
        <div class="cx-container py-12 lg:py-16">
            <p class="eyebrow">فريق التدريس</p>
            <h1 class="mt-4 text-3xl font-bold sm:text-4xl">تعرّف على المحاضرين</h1>
            <p class="mt-3 max-w-2xl text-muted">شاهد الفيديو التعريفي والمحاضرة التجريبية لكل محاضر قبل أن تسجّل، واختر الأسلوب الأقرب لك.</p>
        </div>
    </section>

    <div class="cx-container grid gap-6 py-12 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($instructors as $profile)
            <a href="{{ route('instructors.show', $profile) }}" class="card card-hover group flex flex-col p-6">
                <div class="flex items-center gap-4">
                    <span class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl bg-navy font-display text-xl font-semibold text-white shadow-[4px_4px_0_var(--cx-sky)]">{{ $profile->user->initials() }}</span>
                    <div class="min-w-0">
                        <h2 class="truncate text-lg font-semibold group-hover:text-navy">{{ $profile->user->name }}</h2>
                        <p class="text-sm text-muted">{{ $profile->department?->name }}</p>
                    </div>
                </div>
                <p class="mt-5 line-clamp-2 flex-1 text-[0.95rem] leading-7 text-graphite">{{ $profile->headline }}</p>
                <div class="mt-5 flex flex-wrap gap-1.5">
                    @foreach (array_slice($profile->specialties ?? [], 0, 3) as $skill)
                        <span class="chip chip-navy">{{ $skill }}</span>
                    @endforeach
                </div>
                <div class="mt-6 flex items-center justify-between border-t border-line pt-4 text-sm">
                    <span class="font-mono text-ink">★ {{ $profile->rating }} <span class="text-muted">({{ $profile->ratings_count }})</span></span>
                    <span class="text-muted">{{ $profile->experience_years }} سنوات خبرة · {{ $profile->courses_count }} كورس</span>
                </div>
            </a>
        @endforeach
    </div>
</x-layouts.site>
