<div>
    <div class="card flex flex-col gap-3 p-3 lg:flex-row lg:items-center">
        <label class="relative flex-1">
            <span class="sr-only">ابحث عن كورس</span>
            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute start-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-muted" />
            <input type="search" wire:model.live.debounce.350ms="q" placeholder="ابحث عن كورس…" class="input ps-11" />
        </label>

        <div class="flex flex-wrap gap-2" role="group" aria-label="نوع الكورس">
            <button type="button" wire:click="$set('type', '')" @class(['btn btn-sm', 'btn-primary' => $type === '', 'btn-ghost' => $type !== '']) aria-pressed="{{ $type === '' ? 'true' : 'false' }}">الكل</button>
            @foreach ($types as $t)
                <button type="button" wire:click="$set('type', '{{ $t->value }}')" @class(['btn btn-sm', 'btn-primary' => $type === $t->value, 'btn-ghost' => $type !== $t->value]) aria-pressed="{{ $type === $t->value ? 'true' : 'false' }}">{{ $t->getLabel() }}</button>
            @endforeach
        </div>

        <div class="grid grid-cols-2 gap-2 lg:w-[22rem]">
            <label><span class="sr-only">القسم</span>
                <select wire:model.live="department" class="input">
                    <option value="">كل الأقسام</option>
                    @foreach ($departments as $d)
                        <option value="{{ $d->slug }}">{{ $d->name }}</option>
                    @endforeach
                </select>
            </label>
            <label><span class="sr-only">الترتيب</span>
                <select wire:model.live="sort" class="input">
                    <option value="featured">الأبرز</option>
                    <option value="newest">الأحدث</option>
                    <option value="price_asc">السعر: الأقل</option>
                    <option value="price_desc">السعر: الأعلى</option>
                </select>
            </label>
        </div>
    </div>

    <div class="mt-6 flex items-center justify-between text-sm text-muted">
        <p aria-live="polite"><span class="font-mono font-semibold text-ink">{{ $courses->total() }}</span> كورس متاح</p>
        @if ($q || $type || $department || $sort !== 'featured')
            <button type="button" wire:click="clear" class="font-medium text-navy hover:underline">مسح الفلاتر</button>
        @endif
    </div>

    <div class="relative mt-4">
        <div wire:loading.delay class="absolute inset-0 z-10 grid place-items-start justify-center bg-surface/60 pt-20 backdrop-blur-[1px]">
            <x-brand.mark animated class="w-14" wire:key="loader-{{ now()->timestamp }}" />
        </div>

        @if ($courses->isEmpty())
            <div class="card grid place-items-center gap-3 px-6 py-20 text-center">
                <x-heroicon-o-magnifying-glass class="h-10 w-10 text-sky" />
                <h2 class="text-lg font-semibold">لا توجد كورسات مطابقة</h2>
                <p class="text-muted">جرّب كلمات بحث أخرى أو امسح الفلاتر.</p>
                <button type="button" wire:click="clear" class="btn btn-ghost btn-sm mt-2">عرض كل الكورسات</button>
            </div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($courses as $course)
                    <x-course-card :course="$course" wire:key="course-{{ $course->id }}" />
                @endforeach
            </div>
            <div class="mt-10">{{ $courses->onEachSide(1)->links() }}</div>
        @endif
    </div>
</div>
