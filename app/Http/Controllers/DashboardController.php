<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user=auth()->user(); $today=now()->startOfDay(); $completed=Sale::query()->where('status',SaleStatus::Completed)->where('completed_at','>=',$today)->when($user->isKasir(),fn($q)=>$q->where('completed_by',$user->id)); $base=['todaySales'=>(int)(clone $completed)->sum('total'),'todayTransactions'=>(int)(clone $completed)->count(),'activeDrafts'=>Sale::query()->where('status',SaleStatus::Draft)->count()];
        if($user->isKasir()) return view('dashboard.index',array_merge($base,['isAdmin'=>false]));
        $chart=Sale::query()->where('status',SaleStatus::Completed)->where('completed_at','>=',now()->subDays(6)->startOfDay())->selectRaw('DATE(completed_at) as report_date, SUM(total) as total')->groupByRaw('DATE(completed_at)')->pluck('total','report_date'); $labels=[];$values=[];for($i=6;$i>=0;$i--){$date=now()->subDays($i);$labels[]=$date->format('d/m');$values[]=(int)($chart[$date->toDateString()]??0);}
        $top=DB::table('sale_items')->join('sales','sales.id','=','sale_items.sale_id')->where('sales.status',SaleStatus::Completed)->whereBetween('sales.completed_at',[now()->startOfMonth(),now()->endOfDay()])->select('sale_items.product_name')->selectRaw('SUM(sale_items.quantity) as quantity, SUM(sale_items.subtotal) as omzet')->groupBy('sale_items.product_id','sale_items.product_name')->orderByDesc('quantity')->limit(5)->get(); $low=Product::query()->with('category')->where('min_stock','>',0)->whereColumn('stock','<=','min_stock')->orderBy('stock')->get();
        return view('dashboard.index',array_merge($base,['isAdmin'=>true,'lowStock'=>$low->take(10),'topProducts'=>$top,'chartLabels'=>$labels,'chartValues'=>$values,'lowCount'=>$low->count()]));
    }
}
