<?php

namespace App\Http\Controllers;

use App\Exceptions\DraftConflictException;
use App\Http\Requests\SaleCartRequest;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DraftController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Sale::query()->with(['user', 'lockedBy'])->withCount('items')->where('status', 'draft')->latest('drafted_at');
        if ($request->boolean('count_only')) return response()->json(['count' => $query->count()]);
        $drafts = $query->get()->map(function (Sale $sale): array {
            $locked = $sale->locked_by && $sale->locked_at && $sale->locked_at->gt(now()->subMinutes(config('pos.draft_lock_minutes')));
            return ['id'=>$sale->id,'draft_number'=>$sale->draft_number,'label'=>$sale->label,'items_count'=>$sale->items_count,'total'=>(int)$sale->total,'created_by'=>$sale->user->name,'drafted_at'=>$sale->drafted_at?->toIso8601String(),'drafted_human'=>$sale->drafted_at?->diffForHumans(),'locked_by'=>$locked?$sale->lockedBy?->name:null,'can_resume'=>!$locked||$sale->locked_by===auth()->id(),'can_discard'=>(auth()->user()->isAdmin()||$sale->user_id===auth()->id())&&(!$locked||$sale->locked_by===auth()->id())];
        });
        return response()->json(['count'=>$drafts->count(),'data'=>$drafts]);
    }

    public function store(SaleCartRequest $request, SaleService $service): JsonResponse
    {
        $draft=$service->saveDraft($request->user(),$request->validated());
        return response()->json(['message'=>'Draft '.$draft->draft_number.' tersimpan.','data'=>['id'=>$draft->id,'draft_number'=>$draft->draft_number,'label'=>$draft->label,'total'=>(int)$draft->total],'active_drafts'=>Sale::where('status','draft')->count()],201);
    }

    public function update(SaleCartRequest $request, Sale $sale, SaleService $service): JsonResponse
    {
        try { $draft=$service->updateDraft($request->user(),$sale,$request->validated()); return response()->json(['message'=>'Draft diperbarui.','data'=>['id'=>$draft->id,'draft_number'=>$draft->draft_number,'label'=>$draft->label,'total'=>(int)$draft->total]]); }
        catch (DraftConflictException $e) { return response()->json(['message'=>$e->getMessage()],409); }
    }

    public function resume(Request $request, Sale $sale, SaleService $service): JsonResponse
    {
        try { $data=$service->resumeDraft($request->user(),$sale); return response()->json(['message'=>'Draft dimuat.','data'=>['draft'=>['id'=>$data['draft']->id,'draft_number'=>$data['draft']->draft_number,'label'=>$data['draft']->label,'discount_type'=>$data['draft']->discount_type,'discount_value'=>(int)$data['draft']->discount_value],'items'=>$data['items']],'warnings'=>$data['warnings']]); }
        catch (DraftConflictException $e) { return response()->json(['message'=>$e->getMessage()],409); }
    }

    public function release(Request $request, Sale $sale, SaleService $service): JsonResponse
    {
        try { $service->releaseDraft($request->user(),$sale); return response()->json(['message'=>'Draft dilepas.']); }
        catch (DraftConflictException $e) { return response()->json(['message'=>$e->getMessage()],409); }
    }

    public function destroy(Request $request, Sale $sale, SaleService $service): JsonResponse
    {
        try { $service->discardDraft($request->user(),$sale); return response()->json(['message'=>'Draft dibuang.','active_drafts'=>Sale::where('status','draft')->count()]); }
        catch (DraftConflictException $e) { return response()->json(['message'=>$e->getMessage()],409); }
    }
}
