<x-layouts.site :title="$profile->user->name" :description="$profile->headline">
    <section class="cx-grid-bg border-b border-line">
        <div class="cx-container grid items-center gap-10 py-12 lg:grid-cols-[1fr_1.1fr] lg:py-16">
            <div>
                <a href="{{ route('instructors.index') }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-navy"><x-heroicon-m-arrow-right class="h-4 w-4" /> كل المحاضرين</a>
                <div class="mt-6 flex items-center gap-5">
                    <span class="grid h-20 w-20 shrink-0 place-items-center rounded-2xl bg-navy font-display text-2xl font-semibold text-white shadow-[5px_5px_0_var(--cx-sky)]">{{ $profile->user->initials() }}</span>
                    <div>
                        <h1 class="text-3xl font-bold">{{ $profile->user->name }}</h1>
                        <p class="mt-1 text-muted">{{ $profile->headline }}</p>
                    </div>
                </div>
                <dl class="mt-8 grid grid-cols-3 gap-4">
                    <div class="card p-4"><dt class="text-xs text-muted">التقييم العام</dt><dd class="mt-1 font-mono text-xl font-semibold">★ {{ $profile->rating }}</dd></div>
                    <div class="card p-4"><dt class="text-xs text-muted">سنوات الخبرة</dt><dd class="mt-1 font-mono text-xl font-semibold">{{ $profile->experience_years }}</dd></div>
                    <div class="card p-4"><dt class="text-xs text-muted">الكورسات</dt><dd class="mt-1 font-mono text-xl font-semibold">{{ $courses->count() }}</dd></div>
                </dl>
            </div>

            <div x-data="{ tab: 'intro' }">
                <div class="mb-3 inline-flex rounded-xl bg-white p-1 shadow-[0_0_0_1px_var(--cx-line)]" role="tablist" aria-label="الفيديوهات">
                    <button type="button" role="tab" x-on:click="tab = 'intro'" :aria-selected="tab === 'intro'" :class="tab === 'intro' ? 'bg-navy text-white' : 'text-graphite'" class="rounded-lg px-4 py-2 text-sm font-semibold transition-colors">فيديو تعريفي <span class="font-mono text-xs opacity-70">1–2 د</span></button>
                    <button type="button" role="tab" x-on:click="tab = 'demo'" :aria-selected="tab === 'demo'" :class="tab === 'demo' ? 'bg-navy text-white' : 'text-graphite'" class="rounded-lg px-4 py-2 text-sm font-semibold transition-colors">محاضرة تجريبية <span class="font-mono text-xs opacity-70">5–10 د</span></button>
                </div>
                <div x-show="tab === 'intro'" role="tabpanel"><x-video-frame :url="$introUrl" :title="'تعرّف على '.$profile->user->name" duration="01:45" /></div>
                <div x-show="tab === 'demo'" x-cloak role="tabpanel"><x-video-frame :url="$demoUrl" :title="$profile->demo_video_title ?? 'محاضرة تجريبية'" duration="08:30" /></div>
            </div>
        </div>
    </section>

    <div class="cx-container grid gap-10 py-12 lg:grid-cols-[1fr_22rem]">
        <section aria-labelledby="bio-title">
            <h2 id="bio-title" class="text-2xl font-bold">السيرة الذاتية</h2>
            <p class="mt-4 leading-8 text-graphite">{{ $profile->bio }}</p>

            @if ($courses->isNotEmpty())
                <h2 class="mt-12 text-2xl font-bold">كورسات {{ $profile->user->name }}</h2>
                <div class="mt-6 grid gap-6 sm:grid-cols-2">
                    @foreach ($courses as $course)
                        <x-course-card :course="$course" />
                    @endforeach
                </div>
            @endif
        </section>

        <aside class="grid content-start gap-6">
            <div class="card p-6">
                <h2 class="font-semibold">التخصصات</h2>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($profile->specialties ?? [] as $skill)<span class="chip chip-navy">{{ $skill }}</span>@endforeach
                </div>
                <h2 class="mt-6 font-semibold">الخبرات والمؤهلات</h2>
                <ul class="mt-3 grid gap-2 text-sm">
                    @foreach ($profile->qualifications ?? [] as $item)<li class="cx-check">{{ $item }}</li>@endforeach
                </ul>
            </div>
            @if ($reviews->isNotEmpty())
                <div class="card p-6">
                    <h2 class="font-semibold">ماذا يقول الطلاب</h2>
                    <ul class="mt-4 grid gap-4">
                        @foreach ($reviews->take(3) as $review)
                            <li class="border-b border-line pb-4 text-sm last:border-0 last:pb-0">
                                <p class="leading-7">«{{ $review->comment }}»</p>
                                <p class="mt-2 text-xs text-muted">{{ $review->user->name }} · {{ $review->course?->title }}</p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </div>
</x-layouts.site>
