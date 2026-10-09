<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketMessageResponseTest extends TestCase
{
    use RefreshDatabase;

    private function createRoles(): array
    {
        $customerRole = Role::create([
            'name' => 'Customer',
        ]);

        $agentRole = Role::create([
            'name' => 'Agent',
        ]);

        $permission = Permission::create([
            'name' => 'tickets.view',
            'description' => 'View tickets',
        ]);

        DB::table('role_permissions')->insert([
            [
                'role_id' => $customerRole->id,
                'permission_id' => $permission->id,
            ],
            [
                'role_id' => $agentRole->id,
                'permission_id' => $permission->id,
            ],
        ]);

        return [
            'customer' => $customerRole,
            'agent' => $agentRole,
        ];
    }

    private function createOpenStatus(): TicketStatus
    {
        return TicketStatus::create([
            'name' => 'Open',
            'is_closed' => false,
        ]);
    }

    private function createUser(int $roleId, string $email): User
    {
        return User::create([
            'role_id' => $roleId,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
    }

    private function createTicket(
        User $customer,
        TicketStatus $status
    ): Ticket {
        $category = TicketCategory::create([
            'name' => 'Technical Support',
        ]);

        $priority = TicketPriority::create([
            'name' => 'Medium',
            'level' => 2,
        ]);

        return Ticket::create([
            'ticket_number' => 'TKT-' . strtoupper(
                substr(md5(uniqid()), 0, 8)
            ),
            'customer_id' => $customer->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'subject' => 'Test first response',
            'description' => 'Testing first response behavior.',
            'first_response_at' => null,
        ]);
    }

    public function test_customer_message_does_not_set_first_response_at(): void
    {
        $roles = $this->createRoles();
        $status = $this->createOpenStatus();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer@test.local'
        );

        $ticket = $this->createTicket($customer, $status);

        Sanctum::actingAs($customer);

        $response = $this->postJson(
            "/api/tickets/{$ticket->id}/messages",
            [
                'message' => 'I still cannot access my account.',
            ]
        );

        $response->assertSuccessful();

        $ticket->refresh();

        $this->assertNull($ticket->first_response_at);
    }

    public function test_first_agent_message_sets_first_response_at_only_once(): void
    {
        $roles = $this->createRoles();
        $status = $this->createOpenStatus();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer2@test.local'
        );

        $agent = $this->createUser(
            $roles['agent']->id,
            'agent@test.local'
        );

        $ticket = $this->createTicket($customer, $status);

        TicketAssignment::create([
            'ticket_id' => $ticket->id,
            'agent_id' => $agent->id,
            'assigned_by' => $agent->id,
            'assigned_at' => now(),
        ]);

        Sanctum::actingAs($agent);

        $response = $this->postJson(
            "/api/tickets/{$ticket->id}/messages",
            [
                'message' => 'Hello, I am checking your issue.',
            ]
        );

        $response->assertSuccessful();

        $ticket->refresh();

        $this->assertNotNull($ticket->first_response_at);

        $firstResponseAt = $ticket->first_response_at;

        $response = $this->postJson(
            "/api/tickets/{$ticket->id}/messages",
            [
                'message' => 'I have checked the account.',
            ]
        );

        $response->assertSuccessful();

        $ticket->refresh();

        $this->assertEquals(
            $firstResponseAt->toDateTimeString(),
            $ticket->first_response_at->toDateTimeString()
        );
    }
}