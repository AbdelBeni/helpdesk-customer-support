<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createRoles();
        $this->createStatuses();
    }

    private function createRoles(): void
    {
        Role::firstOrCreate([
            'name' => 'Customer',
        ]);

        Role::firstOrCreate([
            'name' => 'Agent',
        ]);

        Role::firstOrCreate([
            'name' => 'Admin',
        ]);
    }

    private function createStatuses(): void
    {
        foreach ([
            ['name' => 'Open', 'description' => 'Open ticket'],
            ['name' => 'In Progress', 'description' => 'Ticket in progress'],
            ['name' => 'Waiting for Customer', 'description' => 'Waiting for customer'],
            ['name' => 'Resolved', 'description' => 'Resolved ticket'],
            ['name' => 'Closed', 'description' => 'Closed ticket'],
        ] as $status) {
            TicketStatus::firstOrCreate(
                ['name' => $status['name']],
                $status
            );
        }
    }

    private function createUser(string $roleName): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();

        $user = User::create([
            'role_id' => $role->id,
            'first_name' => 'Test',
            'last_name' => $roleName,
            'email' => fake()->unique()->safeEmail(),
            'password' => 'Password123!',
        ]);

        $user->is_active = true;
        $user->save();

        return $user;
    }

    private function authenticatedGet(
        User $user,
        string $uri
    ) {
        $token = $user->createToken('dashboard-test')->plainTextToken;

        return $this->withToken($token)
            ->getJson($uri);
    }

    public function test_customer_cannot_access_dashboard_stats(): void
    {
        $customer = $this->createUser('Customer');

        $response = $this->authenticatedGet(
            $customer,
            '/api/dashboard/stats'
        );

        $response->assertStatus(403);
    }

    public function test_agent_can_access_dashboard_stats(): void
    {
        $agent = $this->createUser('Agent');

        $response = $this->authenticatedGet(
            $agent,
            '/api/dashboard/stats'
        );

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'total_tickets',
            'open_tickets',
            'in_progress_tickets',
            'waiting_for_customer_tickets',
            'resolved_tickets',
            'closed_tickets',
            'unassigned_tickets',
            'tickets_by_priority',
            'tickets_by_category',
            'average_resolution_time' => [
                'minutes',
                'hours',
            ],
        ]);
    }

    public function test_admin_can_access_dashboard_stats(): void
    {
        $admin = $this->createUser('Admin');

        $response = $this->authenticatedGet(
            $admin,
            '/api/dashboard/stats'
        );

        $response->assertStatus(200);
    }

    public function test_customer_cannot_access_ticket_trends(): void
    {
        $customer = $this->createUser('Customer');

        $response = $this->authenticatedGet(
            $customer,
            '/api/dashboard/ticket-trends'
        );

        $response->assertStatus(403);
    }

    public function test_agent_can_access_ticket_trends(): void
    {
        $agent = $this->createUser('Agent');

        $response = $this->authenticatedGet(
            $agent,
            '/api/dashboard/ticket-trends'
        );

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'period',
            'from',
            'to',
            'data',
        ]);
    }

    public function test_ticket_trends_rejects_invalid_period(): void
    {
        $agent = $this->createUser('Agent');

        $response = $this->authenticatedGet(
            $agent,
            '/api/dashboard/ticket-trends?period=15'
        );

        $response->assertStatus(422);
    }

    public function test_ticket_trends_supports_seven_day_period(): void
    {
        $agent = $this->createUser('Agent');

        $response = $this->authenticatedGet(
            $agent,
            '/api/dashboard/ticket-trends?period=7'
        );

        $response->assertStatus(200);

        $response->assertJson([
            'period' => 7,
        ]);

        $this->assertCount(
            7,
            $response->json('data')
        );
    }

    public function test_ticket_trends_supports_thirty_day_period(): void
    {
        $agent = $this->createUser('Agent');

        $response = $this->authenticatedGet(
            $agent,
            '/api/dashboard/ticket-trends?period=30'
        );

        $response->assertStatus(200);

        $response->assertJson([
            'period' => 30,
        ]);

        $this->assertCount(
            30,
            $response->json('data')
        );
    }

    public function test_customer_cannot_access_agent_performance(): void
    {
        $customer = $this->createUser('Customer');

        $response = $this->authenticatedGet(
            $customer,
            '/api/dashboard/agent-performance'
        );

        $response->assertStatus(403);
    }

    public function test_agent_can_access_agent_performance(): void
    {
        $agent = $this->createUser('Agent');

        $response = $this->authenticatedGet(
            $agent,
            '/api/dashboard/agent-performance'
        );

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data',
        ]);
    }

    public function test_customer_cannot_access_response_performance(): void
    {
        $customer = $this->createUser('Customer');

        $response = $this->authenticatedGet(
            $customer,
            '/api/dashboard/response-performance'
        );

        $response->assertStatus(403);
    }

    public function test_agent_can_access_response_performance(): void
    {
        $agent = $this->createUser('Agent');

        $response = $this->authenticatedGet(
            $agent,
            '/api/dashboard/response-performance'
        );

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'total_tickets',
            'responded_tickets',
            'tickets_without_response',
            'response_rate',
            'average_first_response_time' => [
                'minutes',
                'hours',
            ],
        ]);
    }

    public function test_dashboard_stats_returns_zero_values_when_no_tickets_exist(): void
    {
        $admin = $this->createUser('Admin');

        $response = $this->authenticatedGet(
            $admin,
            '/api/dashboard/stats'
        );

        $response->assertStatus(200);

        $response->assertJson([
            'total_tickets' => 0,
            'open_tickets' => 0,
            'in_progress_tickets' => 0,
            'waiting_for_customer_tickets' => 0,
            'resolved_tickets' => 0,
            'closed_tickets' => 0,
            'unassigned_tickets' => 0,
            'average_resolution_time' => [
                'minutes' => null,
                'hours' => null,
            ],
        ]);
    }

    public function test_response_performance_returns_zero_rate_when_no_tickets_exist(): void
    {
        $admin = $this->createUser('Admin');

        $response = $this->authenticatedGet(
            $admin,
            '/api/dashboard/response-performance'
        );

        $response->assertStatus(200);

        $response->assertJson([
            'total_tickets' => 0,
            'responded_tickets' => 0,
            'tickets_without_response' => 0,
            'response_rate' => 0,
            'average_first_response_time' => [
                'minutes' => null,
                'hours' => null,
            ],
        ]);
    }
}