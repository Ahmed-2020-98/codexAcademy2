<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @include('layouts.partials.head', ['title' => 'تقرير الأرباح والخسائر'])
    <style>@page { size: A4; margin: 14mm; }</style>
</head>
<body class="bg-white">
    @php($cur = \App\Models\Setting::get('currency'))
    <div class="mx-auto max-w-3xl px-6 py-10">
        <div class="no-print mb-8 flex justify-end"><button onclick="window.print()" class="btn btn-primary btn-sm">طباعة / حفظ PDF</button></div>
        <header class="flex items-start justify-between border-b-4 border-navy pb-6">
            <div>
                <x-brand.logo />
                <h1 class="mt-6 text-2xl font-bold">تقرير الأرباح والخسائر</h1>
                <p class="mt-1 text-sm text-muted">الفترة: <span class="font-mono" dir="ltr">{{ $r['from']->toDateString() }} → {{ $r['to']->toDateString() }}</span></p>
            </div>
            <div class="text-end text-xs text-muted">أُصدر في<br><span class="font-mono" dir="ltr">{{ now()->format('Y-m-d H:i') }}</span></div>
        </header>

        <table class="mt-8 w-full text-sm">
            <tbody>
                <tr class="bg-navy-50"><th colspan="2" class="px-4 py-2 text-start font-semibold text-navy">الإيرادات</th></tr>
                @foreach ($r['revenue_by_channel'] as $row)
                    <tr class="border-b border-line"><td class="px-4 py-2">{{ $row['label'] }}</td><td class="px-4 py-2 text-end font-mono">{{ number_format($row['total'], 2) }}</td></tr>
                @endforeach
                <tr class="font-semibold"><td class="px-4 py-2">إجمالي الإيرادات (Gross Revenue)</td><td class="px-4 py-2 text-end font-mono">{{ number_format($r['gross_revenue'], 2) }}</td></tr>

                <tr class="bg-navy-50"><th colspan="2" class="px-4 py-2 text-start font-semibold text-navy">المصروفات التشغيلية</th></tr>
                @foreach ($r['expenses_by_category'] as $row)
                    <tr class="border-b border-line"><td class="px-4 py-2">{{ $row['label'] }}</td><td class="px-4 py-2 text-end font-mono">({{ number_format($row['total'], 2) }})</td></tr>
                @endforeach
                <tr class="font-semibold"><td class="px-4 py-2">إجمالي المصروفات (Operating Expenses)</td><td class="px-4 py-2 text-end font-mono">({{ number_format($r['operating_expenses'], 2) }})</td></tr>

                <tr class="bg-navy-50"><th colspan="2" class="px-4 py-2 text-start font-semibold text-navy">مستحقات المحاضرين</th></tr>
                <tr class="font-semibold"><td class="px-4 py-2">ساعات مكتملة × الأجر المعتمد (Instructor Payables)</td><td class="px-4 py-2 text-end font-mono">({{ number_format($r['instructor_payables'], 2) }})</td></tr>

                <tr class="border-t-4 border-navy text-base font-bold"><td class="px-4 py-4">صافي الربح (Net Profit)</td><td class="px-4 py-4 text-end font-mono {{ $r['net_profit'] < 0 ? 'text-red-700' : 'text-navy' }}">{{ number_format($r['net_profit'], 2) }} {{ $cur }}</td></tr>
                <tr><td class="px-4 text-muted">هامش الربح</td><td class="px-4 text-end font-mono text-muted">{{ $r['margin'] }}%</td></tr>
            </tbody>
        </table>

        <p class="mt-10 rounded-lg bg-surface p-4 text-center font-mono text-xs text-muted" dir="ltr">Net Profit = Gross Revenue − (Operating Expenses + Instructor Payables)</p>
    </div>
    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 400))</script>
</body>
</html>
