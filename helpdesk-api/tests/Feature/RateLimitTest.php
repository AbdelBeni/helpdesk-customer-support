<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        RateLimiter::clear('unknown@example.com|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => 'unknown@example.com',
                'password' => 'WrongPassword123!',
            ]);

            $response->assertStatus(401);
        }

        $response = $this->postJson('/api/auth/login', [
            'email' => 'unknown@example.com',
            'password' => 'WrongPassword123!',
        ]);

        $response->assertStatus(429);
    }

    public function test_register_is_rate_limited_after_three_attempts(): void
    {
        RateLimiter::clear('127.0.0.1');

        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/auth/register', []);

            $response->assertStatus(422);
        }

        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(429);
    }

    public function test_authenticated_api_is_rate_limited_after_120_requests(): void
    {
        $role = Role::create([
            'name' => 'Customer',
        ]);

        $user = User::create([
            'role_id' => $role->id,
            'first_name' => 'Rate',
            'last_name' => 'Test',
            'email' => 'rate-test@example.com',
            'password' => 'Password123!',
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        RateLimiter::clear((string) $user->id);

        for ($i = 0; $i < 120; $i++) {
            $response = $this->getJson(
                '/api/notifications/unread-count'
            );

            $response->assertSuccessful();
        }

        $response = $this->getJson(
            '/api/notifications/unread-count'
        );

        $response->assertStatus(429);
    }
}