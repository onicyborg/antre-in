<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleCartRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function index()
    {
        return view('pos.index', ['categories'=>Category::query()->orderBy('name')->get(), 'setting'=>StoreSetting::current()]);
    }

    public function products(Request $request): JsonResponse
    {
        $query = Product::query()->with(['category','unit'])->where('is_active', true)->when($request->filled('q'), function ($q) use ($request): void { $term = '%'.$request->string('q').'%'; $q->where(fn ($sub) => $sub->where('name','like',$term)->orWhere('sku','like',$term)->orWhere('barcode','like',$term)); })->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->input('category_id')))->orderBy('name')->paginate(24);
        return response()->json(['data'=>$query->getCollection()->map(fn (Product $product): array => ['id'=>$product->id,'sku'=>$product->sku,'barcode'=>$product->barcode,'name'=>$product->name,'category'=>$product->category->name,'unit'=>$product->unit->name,'price'=>(int) $product->sell_price,'stock'=>(int) $product->stock,'image_url'=>$product->image_path ? url('storage/'.$product->image_path) : null]),'meta'=>['current_page'=>$query->currentPage(),'last_page'=>$query->lastPage(),'total'=>$query->total()]]);
    }

    public function checkout(SaleCartRequest $request, SaleService $service): JsonResponse
    {
        $sale = $service->checkout($request->user(), $request->validated());
        return response()->json(['message'=>'Transaksi berhasil.','data'=>['id'=>$sale->id,'invoice_number'=>$sale->invoice_number,'total'=>(int) $sale->total,'paid_amount'=>(int) $sale->paid_amount,'change_amount'=>(int) $sale->change_amount,'receipt_url'=>route('transactions.receipt', $sale)]], 201);
    }
}
