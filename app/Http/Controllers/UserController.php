<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Services\ActivityLogger;

class UserController extends Controller
{
    public function create(): RedirectResponse
    {
        return redirect()->route('users.index');
    }

    public function edit(User $user): RedirectResponse
    {
        return redirect()->route('users.index');
    }

    public function index(): View
    {
        return view('users.index', ['users' => User::query()->latest()->get(), 'roles' => UserRole::cases()]);
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['required', 'boolean'],
        ]);

        $user=User::create($data); $logger->log($request->user(),'created','users',$user->id,null,['name'=>$user->name,'email'=>$user->email,'role'=>$user->role,'is_active'=>$user->is_active]);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, User $user, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->getKey())],
            'role' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($user->is($request->user()) && (! $request->boolean('is_active') || $data['role'] !== UserRole::Admin->value)) {
            return redirect()->route('users.index')->with('error', 'Anda tidak dapat menonaktifkan atau mengubah peran akun sendiri.');
        }

        if (blank($data['password'])) {
            unset($data['password']);
        }

        $old=$user->toArray(); $user->update($data); $new=$user->fresh()->toArray(); unset($old['password'],$new['password']); $logger->log($request->user(),'updated','users',$user->id,$old,$new);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user, ActivityLogger $logger): RedirectResponse
    {
        if ($user->is(request()->user())) {
            return redirect()->route('users.index')->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $hasRelations = (Schema::hasTable('sales') && DB::table('sales')->where('user_id', $user->getKey())->exists())
            || (Schema::hasTable('system_logs') && DB::table('system_logs')->where('user_id', $user->getKey())->exists());

        if ($hasRelations) {
            return redirect()->route('users.index')->with('error', 'Pengguna tidak dapat dihapus karena sudah memiliki transaksi atau log aktivitas. Nonaktifkan saja.');
        }

        $old=$user->toArray(); $user->delete(); $logger->log(request()->user(),'deleted','users',$user->id,$old,null);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
