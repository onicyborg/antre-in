<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function sales(Request $request): View
    {
        [$start,$end]=$this->dates($request); $daily=Sale::query()->where('status',SaleStatus::Completed)->whereBetween('completed_at',[$start,$end])->selectRaw('DATE(completed_at) as report_date, COUNT(*) as transactions, SUM(subtotal) as subtotal, SUM(discount_amount) as discount, SUM(tax_amount) as tax, SUM(total) as total')->groupByRaw('DATE(completed_at)')->orderBy('report_date')->get();
        $profitItems=DB::table('sale_items')->join('sales','sales.id','=','sale_items.sale_id')->where('sales.status',SaleStatus::Completed)->whereBetween('sales.completed_at',[$start,$end])->selectRaw('DATE(sales.completed_at) as report_date, SUM((sale_items.unit_price - sale_items.cost_price) * sale_items.quantity) as gross_before_discount')->groupByRaw('DATE(sales.completed_at)')->pluck('gross_before_discount','report_date'); $discounts=Sale::query()->where('status',SaleStatus::Completed)->whereBetween('completed_at',[$start,$end])->selectRaw('DATE(completed_at) as report_date, SUM(discount_amount) as discount')->groupByRaw('DATE(completed_at)')->pluck('discount','report_date'); $profit=$profitItems->mapWithKeys(fn($value,$date)=>[$date=>(int)$value-(int)($discounts[$date]??0)]);
        $kpi=Sale::query()->where('status',SaleStatus::Completed)->whereBetween('completed_at',[$start,$end])->selectRaw('COUNT(*) as transactions, COALESCE(SUM(total),0) as omzet')->first(); $avg=$kpi->transactions?(int)round($kpi->omzet/$kpi->transactions):0;
        return view('reports.sales',['rows'=>$daily,'profit'=>$profit,'kpi'=>$kpi,'average'=>$avg,'grossProfit'=>(int)$profit->sum(),'startDate'=>$start->toDateString(),'endDate'=>$end->toDateString()]);
    }

    public function products(Request $request): View
    {
        [$start,$end]=$this->dates($request); $rows=DB::table('sale_items')->join('sales','sales.id','=','sale_items.sale_id')->where('sales.status',SaleStatus::Completed)->whereBetween('sales.completed_at',[$start,$end])->select('sale_items.product_id','sale_items.product_name','sale_items.sku')->selectRaw('SUM(sale_items.quantity) as quantity, SUM(sale_items.subtotal) as omzet, SUM((sale_items.unit_price - sale_items.cost_price) * sale_items.quantity) as gross_profit')->groupBy('sale_items.product_id','sale_items.product_name','sale_items.sku')->orderByDesc('quantity')->get();
        return view('reports.products',['rows'=>$rows,'startDate'=>$start->toDateString(),'endDate'=>$end->toDateString(),'kpi'=>['quantity'=>(int)$rows->sum('quantity'),'omzet'=>(int)$rows->sum('omzet'),'gross_profit'=>(int)$rows->sum('gross_profit')]]);
    }

    public function stock(Request $request): View
    {
        [$start,$end]=$this->dates($request); $rows=Product::query()->with('category')->select(['id','sku','name','category_id','stock','cost_price','min_stock'])->orderBy('name')->get(); $totalValue=(int)$rows->sum(fn(Product $product)=>(int)$product->stock*(int)$product->cost_price); return view('reports.stock',['rows'=>$rows,'totalValue'=>$totalValue,'lowCount'=>$rows->filter(fn(Product $p)=>(int)$p->min_stock>0&&(int)$p->stock<=(int)$p->min_stock)->count(),'startDate'=>$start->toDateString(),'endDate'=>$end->toDateString()]);
    }

    public function drafts(Request $request): View
    {
        [$start,$end]=$this->dates($request); $drafts=Sale::query()->with('user')->whereNotNull('drafted_at')->whereBetween('drafted_at',[$start,$end])->select(['id','draft_number','label','user_id','status','drafted_at','completed_at','discard_reason'])->latest('drafted_at')->get(); $durations=$drafts->filter(fn(Sale $sale)=>$sale->status===SaleStatus::Completed&&$sale->completed_at&&$sale->drafted_at)->map(fn(Sale $sale)=>(float)$sale->drafted_at->diffInSeconds($sale->completed_at)/60)->sort()->values(); $median=$durations->count()?($durations->count()%2?$durations->get((int)floor($durations->count()/2)):($durations->get($durations->count()/2-1)+$durations->get($durations->count()/2))/2):0;
        return view('reports.drafts',['rows'=>$drafts,'kpi'=>['created'=>$drafts->count(),'completed'=>$drafts->where('status',SaleStatus::Completed)->count(),'discarded_manual'=>$drafts->where('status',SaleStatus::Discarded)->where('discard_reason','manual')->count(),'expired'=>$drafts->where('status',SaleStatus::Discarded)->where('discard_reason','expired')->count(),'active'=>$drafts->where('status',SaleStatus::Draft)->count(),'average'=>$durations->count()?round($durations->avg(),2):0,'median'=>round($median,2)],'startDate'=>$start->toDateString(),'endDate'=>$end->toDateString()]);
    }

    private function dates(Request $request): array
    {
        $request->validate(['start_date'=>'nullable|date','end_date'=>'nullable|date']); $start=Carbon::parse($request->input('start_date',now()->startOfMonth()->toDateString()))->startOfDay(); $end=Carbon::parse($request->input('end_date',now()->toDateString()))->endOfDay(); return $start->lte($end)?[$start,$end]:[$end->copy()->startOfDay(),$start->copy()->endOfDay()];
    }
}
