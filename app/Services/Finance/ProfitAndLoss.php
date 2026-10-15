<?php

namespace App\Services\Finance;

use App\Enums\CourseType;
use App\Models\Expense;
use App\Models\Payment;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;

/**
 * Net Profit = Gross Revenue − (Operating Expenses + Instructor Payables)
 */
class ProfitAndLoss
{
    public function __construct(private InstructorPayableCalculator $payables) {}

    public function report(CarbonInterface $from, CarbonInterface $to): array
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();

        $revenueByChannel = Payment::query()->paid()
            ->whereBetween('paid_at', [$start, $end])
            ->groupBy('channel')
            ->selectRaw('channel, SUM(amount) as total')
            ->pluck('total', 'channel');

        $channels = collect(CourseType::cases())->mapWithKeys(fn (CourseType $type) => [
            $type->value => [
                'label' => $type->getLabel(),
                'total' => round((float) ($revenueByChannel[$type->value] ?? 0), 2),
            ],
        ]);

        $expensesByCategory = Expense::query()
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->whereBetween('spent_on', [$start->toDateString(), $end->toDateString()])
            ->groupBy('expense_categories.id', 'expense_categories.name', 'expense_categories.color')
            ->selectRaw('expense_categories.name, expense_categories.color, SUM(expenses.amount) as total')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['label' => $row->name, 'color' => $row->color, 'total' => round((float) $row->total, 2)]);

        $grossRevenue = round($channels->sum('total'), 2);
        $operatingExpenses = round($expensesByCategory->sum('total'), 2);
        $instructorPayables = $this->payables->totalForPeriod($start, $end);
        $netProfit = round($grossRevenue - ($operatingExpenses + $instructorPayables), 2);

        return [
            'from' => $start,
            'to' => $end,
            'gross_revenue' => $grossRevenue,
            'operating_expenses' => $operatingExpenses,
            'instructor_payables' => $instructorPayables,
            'net_profit' => $netProfit,
            'margin' => $grossRevenue > 0 ? round($netProfit / $grossRevenue * 100, 1) : 0,
            'revenue_by_channel' => $channels->all(),
            'expenses_by_category' => $expensesByCategory->all(),
            'monthly' => $this->monthly($start, $end),
        ];
    }

    /** Month-by-month series for the chart. */
    protected function monthly(CarbonInterface $start, CarbonInterface $end): array
    {
        $months = [];

        foreach (CarbonPeriod::create($start->copy()->startOfMonth(), '1 month', $end) as $month) {
            $from = $month->copy()->startOfMonth()->max($start);
            $to = $month->copy()->endOfMonth()->min($end);

            $revenue = (float) Payment::query()->paid()->whereBetween('paid_at', [$from, $to])->sum('amount');
            $expenses = (float) Expense::query()->whereBetween('spent_on', [$from->toDateString(), $to->toDateString()])->sum('amount');
            $payables = $this->payables->totalForPeriod($from, $to);

            $months[] = [
                'label' => $month->translatedFormat('M Y'),
                'revenue' => round($revenue, 2),
                'costs' => round($expenses + $payables, 2),
                'net' => round($revenue - $expenses - $payables, 2),
            ];
        }

        return $months;
    }
}
