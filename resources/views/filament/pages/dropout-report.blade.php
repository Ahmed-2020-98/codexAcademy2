<x-filament-panels::page>
    @php($stats = $this->getStats())
    @php($breakdown = $this->getBreakdown())

    <x-filament::section>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <label class="grid gap-1 text-sm">
                <span class="font-medium">من تاريخ</span>
                <x-filament::input.wrapper><x-filament::input type="date" wire:model.live="from" /></x-filament::input.wrapper>
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-medium">إلى تاريخ</span>
                <x-filament::input.wrapper><x-filament::input type="date" wire:model.live="to" /></x-filament::input.wrapper>
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-medium">الكورس</span>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="course">
                        <option value="">كل الكورسات</option>
                        @foreach ($this->getCourses() as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </label>
            <label class="flex items-end gap-2 pb-2 text-sm">
                <x-filament::input.checkbox wire:model.live="approvedOnly" />
                <span>الأسباب المعتمدة فقط</span>
            </label>
        </div>
    </x-filament::section>

    <div class="cx-kpis">
        <div class="cx-kpi"><span>إجمالي المنسحبين</span><strong class="cx-mono">{{ $stats['dropped'] }}</strong></div>
        <div class="cx-kpi"><span>نسبة الانسحاب من التسجيلات</span><strong class="cx-mono">{{ $stats['rate'] }}%</strong></div>
        <div class="cx-kpi is-warn"><span>بانتظار اعتماد الإدارة</span><strong class="cx-mono">{{ $stats['pending_approval'] }}</strong></div>
        <div class="cx-kpi is-sky"><span>قابلون لإعادة الاستهداف</span><strong class="cx-mono">{{ $stats['reactivatable'] }}</strong></div>
    </div>

    <div class="grid gap-6 lg:grid-cols-5">
        <x-filament::section class="lg:col-span-2" heading="توزيع الأسباب" icon="heroicon-o-chart-bar">
            <ul class="cx-bars">
                @forelse ($breakdown as $row)
                    <li>
                        <div class="cx-bars__label"><span>{{ $row['reason'] }}</span><span class="cx-mono">{{ $row['total'] }} · {{ $row['percent'] }}%</span></div>
                        <div class="cx-bars__track" role="progressbar" aria-valuenow="{{ $row['percent'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $row['reason'] }}">
                            <span style="width: {{ $row['percent'] }}%"></span>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-gray-500">لا توجد بيانات في الفترة المحددة.</li>
                @endforelse
            </ul>
        </x-filament::section>

        <x-filament::section class="lg:col-span-3" heading="أحدث حالات الانسحاب" icon="heroicon-o-users">
            <div class="overflow-x-auto">
                <table class="cx-table">
                    <thead><tr><th>الطالب</th><th>الكورس</th><th>السبب</th><th>الاعتماد</th></tr></thead>
                    <tbody>
                        @foreach ($this->getLeads() as $lead)
                            <tr>
                                <td><a class="font-medium text-primary-700 hover:underline" href="{{ \App\Filament\Resources\Leads\LeadResource::getUrl('view', ['record' => $lead]) }}">{{ $lead->name }}</a><div class="cx-mono text-xs text-gray-500" dir="ltr">{{ $lead->phone }}</div></td>
                                <td class="text-gray-600">{{ \Illuminate\Support\Str::limit($lead->course?->title, 26) }}</td>
                                <td><x-filament::badge color="danger" size="sm">{{ $lead->dropoutReason?->name ?? '—' }}</x-filament::badge></td>
                                <td>
                                    @if ($lead->dropout_approved_at)
                                        <x-filament::badge color="success" size="sm" icon="heroicon-m-check">معتمد</x-filament::badge>
                                    @else
                                        <x-filament::badge color="warning" size="sm">بانتظار</x-filament::badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
