<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Services\ActivityLogger;

class UnitController extends Controller
{
    public function create(): RedirectResponse
    {
        return redirect()->route('units.index');
    }

    public function edit(Unit $unit): RedirectResponse
    {
        return redirect()->route('units.index');
    }

    public function index(): View
    {
        return view('units.index', ['units' => Unit::query()->withCount('products')->orderBy('name')->get()]);
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:30', 'unique:units,name'],
        ]);

        $unit=Unit::create($data); $logger->log($request->user(),'created','units',$unit->id,null,$unit->toArray());

        return redirect()->route('units.index')->with('success', 'Satuan berhasil ditambahkan.');
    }

    public function update(Request $request, Unit $unit, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:30', Rule::unique('units', 'name')->ignore($unit->getKey())],
        ]);

        $old=$unit->getOriginal(); $unit->update($data); $logger->log($request->user(),'updated','units',$unit->id,$old,$unit->fresh()->toArray());

        return redirect()->route('units.index')->with('success', 'Satuan berhasil diperbarui.');
    }

    public function destroy(Unit $unit, ActivityLogger $logger): RedirectResponse
    {
        if ($unit->products()->exists()) {
            return redirect()->route('units.index')->with('error', 'Satuan tidak dapat dihapus karena masih dipakai produk.');
        }

        $old=$unit->toArray(); $unit->delete(); $logger->log(request()->user(),'deleted','units',$unit->id,$old,null);

        return redirect()->route('units.index')->with('success', 'Satuan berhasil dihapus.');
    }
}
