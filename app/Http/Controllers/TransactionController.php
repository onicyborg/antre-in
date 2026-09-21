<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Models\StoreSetting;
use App\Services\SaleService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['status'=>'nullable|in:completed,voided,draft,discarded','start_date'=>'nullable|date','end_date'=>'nullable|date']);
        $start=Carbon::parse($request->input('start_date',now()->startOfMonth()->toDateString()))->startOfDay(); $end=Carbon::parse($request->input('end_date',now()->toDateString()))->endOfDay();
        $query=Sale::query()->with(['user','completedBy','voidedBy'])->withCount('items')->when($request->user()->isKasir(),fn($q)=>$q->whereIn('status',[SaleStatus::Completed->value,SaleStatus::Voided->value])->where('completed_by',$request->user()->id))->when($request->filled('status'),fn($q)=>$q->where('status',$request->input('status')))->whereBetween('created_at',[$start,$end])->latest();
        return view('transactions.index',['sales'=>$query->get(),'startDate'=>$start->toDateString(),'endDate'=>$end->toDateString(),'statuses'=>$request->user()->isAdmin()?[SaleStatus::Completed,SaleStatus::Draft,SaleStatus::Voided,SaleStatus::Discarded]:[SaleStatus::Completed,SaleStatus::Voided]]);
    }

    public function show(Sale $sale): View
    {
        Gate::authorize('view',$sale); $sale->load(['items','user','completedBy','voidedBy','statusHistories.user']); $history=$sale->statusHistories;
        if($history->isEmpty()){$history=collect();if($sale->drafted_at)$history->push((object)['to_status'=>SaleStatus::Draft,'note'=>'Draft dibuat.','user'=>$sale->user,'created_at'=>$sale->drafted_at]);if($sale->completed_at)$history->push((object)['to_status'=>SaleStatus::Completed,'note'=>'Transaksi selesai.','user'=>$sale->completedBy,'created_at'=>$sale->completed_at]);if($sale->voided_at)$history->push((object)['to_status'=>SaleStatus::Voided,'note'=>$sale->void_reason,'user'=>$sale->voidedBy,'created_at'=>$sale->voided_at]);if($sale->discarded_at)$history->push((object)['to_status'=>SaleStatus::Discarded,'note'=>'Draft dibuang.','user'=>$sale->user,'created_at'=>$sale->discarded_at]);}
        return view('transactions.show',['sale'=>$sale,'history'=>$history]);
    }

    public function void(Request $request, Sale $sale, SaleService $service): RedirectResponse { $data=$request->validate(['void_reason'=>'required|string|max:255']); $service->void($request->user(),$sale,$data['void_reason']); return redirect()->route('transactions.show',$sale)->with('success','Transaksi berhasil di-void.'); }
    public function discard(Request $request, Sale $sale, SaleService $service): RedirectResponse { $service->discardDraft($request->user(),$sale); return redirect()->route('transactions.show',$sale)->with('success','Draft berhasil dibuang.'); }
    public function receipt(Sale $sale): View { Gate::authorize('view',$sale); return view('transactions.receipt',['sale'=>$sale->load(['items','completedBy','user']),'setting'=>StoreSetting::current()]); }
}
