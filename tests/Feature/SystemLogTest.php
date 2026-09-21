<?php

namespace Tests\Feature;

use App\Models\SystemLog;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SystemLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_and_binary_values_are_removed_from_audit_payload(): void
    {
        $user = User::factory()->admin()->create();
        $log = app(ActivityLogger::class)->log($user, 'updated', 'users', $user->id, [
            'password' => 'rahasia', 'token' => 'token-rahasia',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            'nested' => ['secret' => 'jangan-simpan'],
        ], ['name' => 'Admin Baru']);

        $this->assertNotNull($log);
        $this->assertSame('[disamarkan]', $log->old_values['password']);
        $this->assertSame('[disamarkan]', $log->old_values['token']);
        $this->assertSame('[dihapus: file/binary]', $log->old_values['avatar']);
        $this->assertSame('[disamarkan]', $log->old_values['nested']['secret']);
        $this->assertSame($user->id, $log->user_id);
    }

    public function test_login_and_master_data_activity_are_recorded(): void
    {
        $user = User::factory()->admin()->create(['email' => 'admin@example.test', 'password' => 'password']);
        $this->post(route('login.attempt'), ['email' => 'admin@example.test', 'password' => 'password'])->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('system_logs', ['action' => 'login', 'user_id' => $user->id]);
        $this->post(route('categories.store'), ['name' => 'Minuman'])->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('system_logs', ['action' => 'created', 'table_name' => 'categories']);
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertDatabaseHas('system_logs', ['action' => 'logout', 'user_id' => $user->id]);
    }

    public function test_system_log_screen_is_admin_only_and_limited_to_thirty_days(): void
    {
        $admin = User::factory()->admin()->create();
        $old = SystemLog::create(['user_id' => $admin->id, 'action' => 'updated', 'table_name' => 'users']);
        DB::table('system_logs')->where('id', $old->id)->update(['created_at' => now()->subDays(40)]);
        $recent = SystemLog::create(['user_id' => $admin->id, 'action' => 'updated', 'table_name' => 'users']);
        DB::table('system_logs')->where('id', $recent->id)->update(['created_at' => now()->subDays(2)]);
        $this->actingAs($admin)->get(route('system-logs.index', ['start_date' => now()->subDays(90)->toDateString(), 'end_date' => now()->toDateString()]))
            ->assertOk()->assertViewHas('logs', function ($logs) use ($old, $recent) {
                return $logs->contains('id', $recent->id) && ! $logs->contains('id', $old->id);
            });
        $this->actingAs(User::factory()->kasir()->create())->get(route('system-logs.index'))->assertForbidden();
    }
}
