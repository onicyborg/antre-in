<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Services\ActivityLogger;

class CategoryController extends Controller
{
    public function create(): RedirectResponse
    {
        return redirect()->route('categories.index');
    }

    public function edit(Category $category): RedirectResponse
    {
        return redirect()->route('categories.index');
    }

    public function index(): View
    {
        return view('categories.index', ['categories' => Category::query()->withCount('products')->orderBy('name')->get()]);
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
        ]);

        $category=Category::create($data); $logger->log($request->user(),'created','categories',$category->id,null,$category->toArray());

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, Category $category, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category->getKey())],
        ]);

        $old=$category->getOriginal(); $category->update($data); $logger->log($request->user(),'updated','categories',$category->id,$old,$category->fresh()->toArray());

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category, ActivityLogger $logger): RedirectResponse
    {
        if ($category->products()->exists()) {
            return redirect()->route('categories.index')->with('error', 'Kategori tidak dapat dihapus karena masih dipakai produk.');
        }

        $old=$category->toArray(); $category->delete(); $logger->log(request()->user(),'deleted','categories',$category->id,$old,null);

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
