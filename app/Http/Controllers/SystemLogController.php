<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemLogController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['start_date'=>'nullable|date','end_date'=>'nullable|date']);
        $start = Carbon::parse($request->input('start_date', now()->subDays(29)->toDateString()))->startOfDay();
        $end = Carbon::parse($request->input('end_date', now()->toDateString()))->endOfDay();
        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }
        $minimumStart = $end->copy()->subDays(29)->startOfDay();
        if ($start->lessThan($minimumStart)) {
            $start = $minimumStart;
        }
        return view('system-logs.index',['logs'=>SystemLog::query()->with('user')->whereBetween('created_at',[$start,$end])->latest()->get(),'startDate'=>$start->toDateString(),'endDate'=>$end->toDateString()]);
    }
}
