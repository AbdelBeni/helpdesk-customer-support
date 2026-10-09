<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerRole();
    }

    private function customerRole(): Role
    {
        return Role::firstOrCreate([
            'name' => 'Customer',
        ]);
    }

    private function createUser(bool $isActive = true): User
    {
        $user = User::create([
            'role_id' => $this->customerRole()->id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'Password123!',
        ]);

        $user->is_active = $isActive;
        $user->save();

        return $user;
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'success',
            'message',
            'user',
            'token',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/auth/register', [
            'first_name' => 'Another',
            'last_name' => 'User',
            'email' => $user->email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'email',
        ]);
    }

    public function test_registration_requires_valid_data(): void
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'first_name',
            'last_name',
            'email',
            'password',
        ]);
    }

    public function test_user_can_login(): void
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'success',
            'message',
            'user',
            'token',
        ]);

        $this->assertNotEmpty(
            $response->json('token')
        );
    }

    public function test_login_rejects_invalid_password(): void
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'WrongPassword123!',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_rejects_unknown_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'unknown@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(401);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = $this->createUser(false);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $response->assertStatus(403);
    }

    public function test_current_user_requires_authentication(): void
        {
            $response = $this->getJson('/api/auth/me');

            $response->assertStatus(401);
        }

        public function test_authenticated_user_can_access_current_user(): void
    {
        $user = $this->createUser();

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/auth/me');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'id' => $user->id,
            'email' => $user->email,
        ]);
    }

    public function test_user_can_logout(): void
    {
        $user = $this->createUser();

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_logged_out_token_cannot_be_used(): void
    {
        $user = $this->createUser();

        $token = $user->createToken('test-token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertStatus(200);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);

        $this->app['auth']->forgetGuards();

        $response = $this->withToken($token)
            ->getJson('/api/auth/me');

        $response->assertStatus(401);
    }
}