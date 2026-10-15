<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Finance\ProfitAndLoss;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Print-optimised P&L (browser "Save as PDF" keeps Arabic shaping intact). */
class ProfitLossPrintController extends Controller
{
    public function __invoke(Request $request, ProfitAndLoss $pl): View
    {
        $from = Carbon::parse($request->query('from', now()->startOfMonth()->toDateString()));
        $to = Carbon::parse($request->query('to', now()->toDateString()));

        return view('admin.pl-print', ['r' => $pl->report($from, $to)]);
    }
}
