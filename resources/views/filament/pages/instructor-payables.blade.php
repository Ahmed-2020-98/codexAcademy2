<x-filament-panels::page>
    @php($rows = $this->getRows())

    <div class="flex flex-wrap items-end gap-4">
        <label class="grid gap-1 text-sm">
            <span class="font-medium">الشهر</span>
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="month">
                    @foreach ($this->getMonths() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </label>
    </div>

    <div class="cx-kpis">
        <div class="cx-kpi"><span>إجمالي الساعات</span><strong class="cx-mono">{{ number_format($rows->sum('hours'), 1) }}</strong></div>
        <div class="cx-kpi"><span>إجمالي المستحقات</span><strong class="cx-mono">{{ number_format($rows->sum('amount')) }}</strong></div>
        <div class="cx-kpi is-sky"><span>المصروف فعلياً</span><strong class="cx-mono">{{ number_format($rows->sum('paid')) }}</strong></div>
        <div class="cx-kpi is-warn"><span>المتبقي للصرف</span><strong class="cx-mono">{{ number_format($rows->sum('outstanding')) }}</strong></div>
    </div>

    <x-filament::section>
        <div class="overflow-x-auto">
            <table class="cx-table">
                <thead>
                    <tr><th>المحاضر</th><th>المحاضرات</th><th>الساعات</th><th>أجر الساعة</th><th>المستحق</th><th>المصروف</th><th>المتبقي</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td class="font-medium">{{ $row['instructor']->name }}</td>
                            <td class="cx-mono">{{ $row['sessions'] }}</td>
                            <td class="cx-mono">{{ number_format($row['hours'], 1) }}</td>
                            <td class="cx-mono">{{ number_format($row['rate']) }}</td>
                            <td class="cx-mono font-semibold">{{ number_format($row['amount'], 2) }}</td>
                            <td class="cx-mono text-success-700">{{ number_format($row['paid'], 2) }}</td>
                            <td class="cx-mono {{ $row['outstanding'] > 0 ? 'text-warning-700 font-semibold' : 'text-gray-400' }}">{{ number_format($row['outstanding'], 2) }}</td>
                            <td class="text-end">
                                @if ($row['outstanding'] > 0)
                                    {{ ($this->payAction)(['instructor' => $row['instructor']->id]) }}
                                @else
                                    <x-filament::badge color="success" size="sm">مُسدد</x-filament::badge>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-panels::page>
