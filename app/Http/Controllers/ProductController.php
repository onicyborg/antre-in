<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Services\ActivityLogger;

class ProductController extends Controller
{
    public function __construct(private readonly StockService $stockService) {}

    public function create(): RedirectResponse
    {
        return redirect()->route('products.index');
    }

    public function edit(Product $product): RedirectResponse
    {
        return redirect()->route('products.index');
    }

    public function index(): View
    {
        return view('products.index', [
            'products' => Product::query()->with(['category', 'unit'])->latest()->get(),
            'categories' => Category::query()->orderBy('name')->get(),
            'units' => Unit::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $initialStock = (int) $data['stock_initial'];
        unset($data['stock_initial']);

        $product = DB::transaction(function () use ($data, $initialStock, $request): Product {
            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image')->store('products', 'public');
            }

            $product = Product::create($data);

            if ($initialStock > 0) {
                $this->stockService->recordMovement($product, $request->user(), StockMovementType::Initial, $initialStock, 'Stok awal produk');
            }

            return $product;
        });

        $logger->log($request->user(),'created','products',$product->id,null,$product->fresh()->toArray()); return redirect()->route('products.index')->with('success', "Produk {$product->name} berhasil ditambahkan.");
    }

    public function update(Request $request, Product $product, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate($this->rules($product));
        unset($data['stock_initial']);

        $old = $product->toArray(); $oldImage = $product->image_path;

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        if ($request->hasFile('image') && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        $logger->log($request->user(),'updated','products',$product->id,$old,$product->fresh()->toArray()); return redirect()->route('products.index')->with('success', "Produk {$product->name} berhasil diperbarui.");
    }

    public function destroy(Product $product, ActivityLogger $logger): RedirectResponse
    {
        $usedInSale = Schema::hasTable('sale_items') && DB::table('sale_items')->where('product_id', $product->getKey())->exists();

        if ($product->stockMovements()->exists() || $usedInSale) {
            return redirect()->route('products.index')->with('error', 'Produk tidak dapat dihapus karena sudah dipakai transaksi atau riwayat stok, nonaktifkan saja.');
        }

        $old = $product->toArray(); $image = $product->image_path;
        $product->delete();
        $logger->log(request()->user(),'deleted','products',$product->id,$old,null);

        if ($image) {
            Storage::disk('public')->delete($image);
        }

        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus.');
    }

    private function rules(?Product $product = null): array
    {
        return [
            'category_id' => ['required', 'uuid', 'exists:categories,id'],
            'unit_id' => ['required', 'uuid', 'exists:units,id'],
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product?->getKey())],
            'barcode' => ['nullable', 'string', 'max:64', Rule::unique('products', 'barcode')->ignore($product?->getKey())],
            'name' => ['required', 'string', 'max:150'],
            'cost_price' => ['required', 'integer', 'min:0'],
            'sell_price' => ['required', 'integer', 'min:0'],
            'stock_initial' => ['required', 'integer', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
