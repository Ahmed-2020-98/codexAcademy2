<x-filament-panels::page>
    @php($columns = $this->getColumns())

    <div class="cx-board-toolbar">
        <label class="cx-board-search">
            <x-filament::icon icon="heroicon-m-magnifying-glass" class="h-4 w-4 text-gray-400" />
            <input type="search" wire:model.live.debounce.400ms="search" placeholder="ابحث بالاسم أو رقم الهاتف…" aria-label="بحث" />
        </label>

        <x-filament::input.wrapper class="cx-board-filter">
            <x-filament::input.select wire:model.live="assignee" aria-label="مسؤول المتابعة">
                <option value="">كل المسؤولين</option>
                @foreach ($this->getAssignees() as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>

        <div class="cx-board-legend">
            <span><i class="is-ok"></i> داخل المهلة</span>
            <span><i class="is-warn"></i> أقل من 4 ساعات</span>
            <span><i class="is-late"></i> متأخر</span>
        </div>
    </div>

    <div
        class="cx-board"
        x-data="{
            dragging: null,
            over: null,
            now: Math.floor(Date.now() / 1000),
            init() { setInterval(() => this.now = Math.floor(Date.now() / 1000), 1000) },
            sla(due) {
                const s = due - this.now, a = Math.abs(s);
                const h = Math.floor(a / 3600), m = Math.floor((a % 3600) / 60), sec = a % 60;
                const t = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(sec).padStart(2,'0')}`;
                return s >= 0 ? t : '−' + t;
            },
            tone(due) { const s = due - this.now; return s < 0 ? 'is-late' : (s < 14400 ? 'is-warn' : 'is-ok') },
            drop(status) {
                if (this.dragging && this.dragging.status !== status) { $wire.moveLead(this.dragging.id, status) }
                this.dragging = null; this.over = null;
            },
        }"
    >
        @foreach (\App\Enums\LeadStatus::cases() as $status)
            @php($leads = $columns[$status->value])
            <section
                class="cx-board__col"
                :class="{ 'is-over': over === '{{ $status->value }}' }"
                x-on:dragover.prevent="over = '{{ $status->value }}'"
                x-on:dragleave.self="over = null"
                x-on:drop.prevent="drop('{{ $status->value }}')"
                aria-label="{{ $status->getLabel() }}"
                style="--col: {{ $status->hex() }}"
            >
                <header class="cx-board__head">
                    <span class="cx-board__pixel"></span>
                    <div>
                        <h3>{{ $status->getLabel() }}</h3>
                        <small dir="ltr">{{ $status->englishLabel() }}</small>
                    </div>
                    <span class="cx-board__count cx-mono">{{ $leads->count() }}</span>
                </header>

                <div class="cx-board__list">
                    @forelse ($leads as $lead)
                        <article
                            wire:key="lead-{{ $lead->id }}"
                            class="cx-card {{ $lead->is_overdue && $status === \App\Enums\LeadStatus::Pending ? 'is-overdue' : '' }}"
                            draggable="true"
                            x-on:dragstart="dragging = { id: {{ $lead->id }}, status: '{{ $status->value }}' }; $event.dataTransfer.effectAllowed = 'move'"
                            x-on:dragend="dragging = null; over = null"
                            :class="{ 'is-dragging': dragging && dragging.id === {{ $lead->id }} }"
                        >
                            <a href="{{ $this->leadUrl($lead) }}" class="cx-card__name">{{ $lead->name }}</a>
                            <p class="cx-card__course">{{ $lead->course?->title ?? 'لم يُحدد كورس' }}</p>

                            <div class="cx-card__meta">
                                <span class="cx-mono" dir="ltr">{{ $lead->phone }}</span>
                                @if ($lead->study_mode)
                                    <x-filament::badge size="sm" :color="$lead->study_mode->getColor()">{{ $lead->study_mode->getLabel() }}</x-filament::badge>
                                @endif
                            </div>

                            <footer class="cx-card__foot">
                                @if ($status === \App\Enums\LeadStatus::Pending && $lead->sla_due_at)
                                    <span class="cx-sla cx-mono" :class="tone({{ $lead->sla_due_at->timestamp }})" x-text="sla({{ $lead->sla_due_at->timestamp }})" title="مؤشر SLA">--:--:--</span>
                                @elseif ($status === \App\Enums\LeadStatus::AssessmentScheduled && $lead->assessment_at)
                                    <span class="cx-card__hint"><x-filament::icon icon="heroicon-m-calendar" class="h-3.5 w-3.5" /> {{ $lead->assessment_at->translatedFormat('d M — h:i A') }}</span>
                                @elseif ($status === \App\Enums\LeadStatus::DroppedOut)
                                    <span class="cx-card__hint is-danger">{{ $lead->dropoutReason?->name }}</span>
                                @else
                                    <span class="cx-card__hint">{{ $lead->updated_at->diffForHumans() }}</span>
                                @endif

                                @if ($lead->assignee)
                                    <span class="cx-avatar" title="{{ $lead->assignee->name }}">{{ $lead->assignee->initials() }}</span>
                                @endif
                            </footer>
                        </article>
                    @empty
                        <p class="cx-board__empty">لا يوجد طلاب في هذه المرحلة</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
