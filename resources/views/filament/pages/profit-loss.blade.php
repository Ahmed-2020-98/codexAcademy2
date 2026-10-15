<x-filament-panels::page>
    @php($r = $this->getReport())
    @php($cur = \App\Models\Setting::get('currency'))

    <x-filament::section>
        <div class="flex flex-wrap items-end gap-4">
            <label class="grid gap-1 text-sm">
                <span class="font-medium">من</span>
                <x-filament::input.wrapper><x-filament::input type="date" wire:model.live="from" /></x-filament::input.wrapper>
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-medium">إلى</span>
                <x-filament::input.wrapper><x-filament::input type="date" wire:model.live="to" /></x-filament::input.wrapper>
            </label>
            <div class="flex flex-wrap gap-2">
                <x-filament::button size="sm" color="gray" wire:click="preset('month')">هذا الشهر</x-filament::button>
                <x-filament::button size="sm" color="gray" wire:click="preset('last_month')">الشهر الماضي</x-filament::button>
                <x-filament::button size="sm" color="gray" wire:click="preset('quarter')">هذا الربع</x-filament::button>
                <x-filament::button size="sm" color="gray" wire:click="preset('year')">منذ بداية العام</x-filament::button>
                <x-filament::button size="sm" color="gray" wire:click="preset('six')">آخر 6 أشهر</x-filament::button>
            </div>
        </div>
    </x-filament::section>

    {{-- The formula, rendered with live numbers. --}}
    <div class="cx-formula" aria-label="معادلة صافي الربح">
        <div class="cx-formula__term">
            <span>إجمالي الإيرادات</span>
            <strong class="cx-mono">{{ number_format($r['gross_revenue']) }}</strong>
            <small dir="ltr">Gross Revenue</small>
        </div>
        <span class="cx-formula__op" aria-hidden="true">−</span>
        <span class="cx-formula__paren" aria-hidden="true">(</span>
        <div class="cx-formula__term">
            <span>المصروفات التشغيلية</span>
            <strong class="cx-mono">{{ number_format($r['operating_expenses']) }}</strong>
            <small dir="ltr">Operating Expenses</small>
        </div>
        <span class="cx-formula__op" aria-hidden="true">+</span>
        <div class="cx-formula__term">
            <span>مستحقات المحاضرين</span>
            <strong class="cx-mono">{{ number_format($r['instructor_payables']) }}</strong>
            <small dir="ltr">Instructor Payables</small>
        </div>
        <span class="cx-formula__paren" aria-hidden="true">)</span>
        <span class="cx-formula__op" aria-hidden="true">=</span>
        <div class="cx-formula__term is-result {{ $r['net_profit'] < 0 ? 'is-loss' : '' }}">
            <span>صافي الربح</span>
            <strong class="cx-mono">{{ number_format($r['net_profit']) }} <em>{{ $cur }}</em></strong>
            <small>هامش {{ $r['margin'] }}%</small>
        </div>
    </div>

    @livewire(\App\Filament\Pages\Widgets\ProfitLossChart::class, ['from' => $from, 'to' => $to], key('pl-chart'))

    <div class="grid gap-6 lg:grid-cols-2">
        <x-filament::section heading="الإيرادات حسب المصدر" icon="heroicon-o-banknotes">
            @php($revMax = max(1, collect($r['revenue_by_channel'])->max('total')))
            <ul class="cx-bars">
                @foreach ($r['revenue_by_channel'] as $row)
                    <li>
                        <div class="cx-bars__label"><span>{{ $row['label'] }}</span><span class="cx-mono">{{ number_format($row['total']) }} {{ $cur }}</span></div>
                        <div class="cx-bars__track"><span style="width: {{ round($row['total'] / $revMax * 100) }}%"></span></div>
                    </li>
                @endforeach
            </ul>
            @if ($this->pendingManualBookings())
                <p class="mt-4 text-xs text-warning-700">لا تشمل {{ $this->pendingManualBookings() }} حجزاً يدوياً بانتظار الاعتماد.</p>
            @endif
        </x-filament::section>

        <x-filament::section heading="المصروفات التشغيلية حسب التصنيف" icon="heroicon-o-receipt-percent">
            @php($expMax = max(1, collect($r['expenses_by_category'])->max('total')))
            <ul class="cx-bars">
                @forelse ($r['expenses_by_category'] as $row)
                    <li>
                        <div class="cx-bars__label"><span>{{ $row['label'] }}</span><span class="cx-mono">{{ number_format($row['total']) }} {{ $cur }}</span></div>
                        <div class="cx-bars__track"><span style="width: {{ round($row['total'] / $expMax * 100) }}%; background: {{ $row['color'] }}"></span></div>
                    </li>
                @empty
                    <li class="text-sm text-gray-500">لا توجد مصروفات في الفترة.</li>
                @endforelse
            </ul>
        </x-filament::section>
    </div>
</x-filament-panels::page>
