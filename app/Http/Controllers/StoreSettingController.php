<?php

namespace App\Http\Controllers;

use App\Models\StoreSetting;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StoreSettingController extends Controller
{
    public function edit() { return view('settings.edit', ['setting'=>StoreSetting::current()]); }
    public function update(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate(['store_name'=>'required|string|max:100','address'=>'nullable|string','phone'=>'nullable|string|max:50','receipt_footer'=>'required|string|max:255','tax_percent'=>'required|numeric|min:0|max:100','draft_expire_hours'=>'required|integer|min:1|max:168','max_active_drafts'=>'required|integer|min:1|max:100']);
        $setting = StoreSetting::current();
        $old = $setting->toArray();
        $setting->update($data);
        $logger->log($request->user(), 'updated', 'store_settings', $setting->id, $old, $setting->fresh()->toArray());
        return back()->with('success', 'Pengaturan toko berhasil diperbarui.');
    }
}
