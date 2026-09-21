<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'active', 'role:admin'])
            ->get('/__test/admin-only', fn () => response('ok'));
    }

    public function test_kasir_gets_forbidden_on_admin_route(): void
    {
        $this->actingAs(User::factory()->kasir()->create())
            ->get('/__test/admin-only')
            ->assertForbidden();
    }

    public function test_admin_is_allowed_on_admin_route(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/__test/admin-only')
            ->assertOk();
    }

    public function test_guest_is_redirected_to_login_before_role_check(): void
    {
        $this->get('/__test/admin-only')->assertRedirect('/login');
    }
}
