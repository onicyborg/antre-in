<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_session_is_regenerated(): void
    {
        $user = User::factory()->create(['password' => 'rahasia']);
        $oldSessionId = $this->app['session']->getId();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'rahasia',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($oldSessionId, $this->app['session']->getId());
    }

    public function test_invalid_login_uses_generic_error(): void
    {
        User::factory()->create(['email' => 'ada@example.test', 'password' => 'rahasia']);

        $response = $this->from('/login')->post('/login', [
            'email' => 'ada@example.test',
            'password' => 'salah',
        ]);

        $response->assertRedirect('/login')->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create(['password' => 'rahasia']);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'rahasia',
        ]);

        $response->assertRedirect('/login')->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_failed_attempts_per_email_and_ip(): void
    {
        RateLimiter::clear('throttle@example.test|127.0.0.1');

        foreach (range(1, 5) as $attempt) {
            $this->from('/login')->post('/login', [
                'email' => 'throttle@example.test',
                'password' => 'salah',
            ]);
        }

        $response = $this->from('/login')->post('/login', [
            'email' => 'throttle@example.test',
            'password' => 'salah',
        ]);

        $response->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertStringContainsString('Terlalu banyak', (string) session('errors')->first('email'));
    }

    public function test_logout_invalidates_session(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
