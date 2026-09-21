<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(private readonly StockService $stockService) {}

    public function index(Request $request): View
    {
        $query = Product::query()->with('category')->orderBy('name');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->string('category_id')->toString());
        }

        if ($request->boolean('low_stock')) {
            $query->where('min_stock', '>', 0)->whereColumn('stock', '<=', 'min_stock');
        }

        return view('stock.index', [
            'products' => $query->get(),
            'categories' => Category::query()->orderBy('name')->get(),
            'selectedCategory' => $request->string('category_id')->toString(),
            'onlyLowStock' => $request->boolean('low_stock'),
        ]);
    }

    public function receive(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'cost_price' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::query()->findOrFail($data['product_id']);
        $this->stockService->receive($product, $request->user(), (int) $data['quantity'], $data['note'] ?? null, isset($data['cost_price']) ? (int) $data['cost_price'] : null);

        return redirect()->route('stock.index')->with('success', "Stok produk {$product->name} berhasil ditambahkan.");
    }

    public function adjust(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'actual_stock' => ['required', 'integer', 'min:0'],
            'note' => ['required', 'string', 'max:255'],
        ]);

        $product = Product::query()->findOrFail($data['product_id']);
        $this->stockService->adjustTo($product, $request->user(), (int) $data['actual_stock'], $data['note']);

        return redirect()->route('stock.index')->with('success', "Stok produk {$product->name} berhasil disesuaikan.");
    }

    public function movements(Request $request): View
    {
        $defaultStart = now()->subDays(29)->startOfDay();
        $defaultEnd = now()->endOfDay();
        [$start, $end] = $this->dateRange($request->input('period'), $defaultStart, $defaultEnd);

        $request->validate([
            'product_id' => ['nullable', 'uuid', 'exists:products,id'],
            'type' => ['nullable', 'string', 'in:'.implode(',', array_column(StockMovementType::cases(), 'value'))],
        ]);

        $query = StockMovement::query()->with(['product', 'user'])->whereBetween('created_at', [$start, $end])->latest();
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->string('product_id')->toString());
        }
        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        return view('stock.movements', [
            'movements' => $query->get(),
            'products' => Product::query()->orderBy('name')->get(),
            'types' => StockMovementType::cases(),
            'period' => $start->format('Y-m-d').' - '.$end->format('Y-m-d'),
        ]);
    }

    private function dateRange(?string $period, Carbon $defaultStart, Carbon $defaultEnd): array
    {
        if (! $period || ! preg_match('/^(\d{4}-\d{2}-\d{2})\s+-\s+(\d{4}-\d{2}-\d{2})$/', $period, $matches)) {
            return [$defaultStart, $defaultEnd];
        }

        try {
            $start = Carbon::createFromFormat('Y-m-d', $matches[1])->startOfDay();
            $end = Carbon::createFromFormat('Y-m-d', $matches[2])->endOfDay();
        } catch (\Throwable) {
            return [$defaultStart, $defaultEnd];
        }

        return $start->lte($end) ? [$start, $end] : [$defaultStart, $defaultEnd];
    }
}
