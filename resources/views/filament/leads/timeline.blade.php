@php
    use App\Enums\LeadStatus;
    $activities = $getRecord()->activities()->with('user')->get();
    $icons = ['created' => 'heroicon-m-sparkles', 'status_change' => 'heroicon-m-arrows-right-left', 'note' => 'heroicon-m-pencil-square', 'sla_breach' => 'heroicon-m-bell-alert', 'whatsapp' => 'heroicon-m-chat-bubble-left-right'];
@endphp

<ol class="cx-timeline">
    @forelse ($activities as $activity)
        @php
            $to = $activity->to_status ? LeadStatus::tryFrom($activity->to_status) : null;
            $from = $activity->from_status ? LeadStatus::tryFrom($activity->from_status) : null;
            $tone = $activity->type === 'sla_breach' ? '#DC2626' : ($to?->hex() ?? '#0A5A9E');
        @endphp
        <li class="cx-timeline__item">
            <span class="cx-timeline__dot" style="background: {{ $tone }}">
                <x-filament::icon :icon="$icons[$activity->type] ?? 'heroicon-m-dot'" class="h-3.5 w-3.5 text-white" />
            </span>
            <div class="cx-timeline__body">
                <div class="cx-timeline__head">
                    <strong>
                        @switch($activity->type)
                            @case('created') تسجيل جديد @break
                            @case('status_change') {{ $from?->getLabel() ?? '—' }} ← {{ $to?->getLabel() }} @break
                            @case('sla_breach') تجاوز الـ SLA @break
                            @default ملاحظة
                        @endswitch
                    </strong>
                    <time class="cx-mono" datetime="{{ $activity->created_at->toIso8601String() }}">{{ $activity->created_at->translatedFormat('d M Y — h:i A') }}</time>
                </div>
                @if ($activity->body)
                    <p>{{ $activity->body }}</p>
                @endif
                <span class="cx-timeline__by">{{ $activity->user?->name ?? 'النظام' }}</span>
            </div>
        </li>
    @empty
        <li class="text-sm text-gray-500">لا توجد أنشطة بعد.</li>
    @endforelse
</ol>
