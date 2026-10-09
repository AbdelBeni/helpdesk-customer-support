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

class TicketAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function createRoles(): array
    {
        $adminRole = Role::create([
            'name' => 'Admin',
        ]);

        $agentRole = Role::create([
            'name' => 'Agent',
        ]);

        $customerRole = Role::create([
            'name' => 'Customer',
        ]);

        $permission = Permission::create([
            'name' => 'tickets.view',
            'description' => 'View tickets',
        ]);

        DB::table('role_permissions')->insert([
            [
                'role_id' => $adminRole->id,
                'permission_id' => $permission->id,
            ],
            [
                'role_id' => $agentRole->id,
                'permission_id' => $permission->id,
            ],
            [
                'role_id' => $customerRole->id,
                'permission_id' => $permission->id,
            ],
        ]);

        return [
            'admin' => $adminRole,
            'agent' => $agentRole,
            'customer' => $customerRole,
        ];
    }

    private function createUser(
        int $roleId,
        string $email,
        string $firstName
    ): User {
        return User::create([
            'role_id' => $roleId,
            'first_name' => $firstName,
            'last_name' => 'User',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
    }

    private function createStatus(): TicketStatus
    {
        return TicketStatus::create([
            'name' => 'Open',
            'is_closed' => false,
        ]);
    }

    private function createTicket(
        User $customer,
        TicketStatus $status,
        string $subject
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
            'subject' => $subject,
            'description' => 'Authorization test ticket.',
        ]);
    }

    public function test_customer_cannot_view_another_customers_ticket(): void
    {
        $roles = $this->createRoles();
        $status = $this->createStatus();

        $customerA = $this->createUser(
            $roles['customer']->id,
            'customer-a@test.local',
            'Customer A'
        );

        $customerB = $this->createUser(
            $roles['customer']->id,
            'customer-b@test.local',
            'Customer B'
        );

        $ticket = $this->createTicket(
            $customerA,
            $status,
            'Private customer ticket'
        );

        Sanctum::actingAs($customerB);

        $response = $this->getJson(
            "/api/tickets/{$ticket->id}"
        );

        $response->assertForbidden();
    }

    public function test_agent_cannot_view_unassigned_ticket(): void
    {
        $roles = $this->createRoles();
        $status = $this->createStatus();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer@test.local',
            'Customer'
        );

        $agent = $this->createUser(
            $roles['agent']->id,
            'agent@test.local',
            'Agent'
        );

        $ticket = $this->createTicket(
            $customer,
            $status,
            'Unassigned ticket'
        );

        Sanctum::actingAs($agent);

        $response = $this->getJson(
            "/api/tickets/{$ticket->id}"
        );

        $response->assertForbidden();
    }

    public function test_agent_can_view_assigned_ticket(): void
    {
        $roles = $this->createRoles();
        $status = $this->createStatus();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer2@test.local',
            'Customer'
        );

        $agent = $this->createUser(
            $roles['agent']->id,
            'agent2@test.local',
            'Agent'
        );

        $ticket = $this->createTicket(
            $customer,
            $status,
            'Assigned ticket'
        );

        TicketAssignment::create([
            'ticket_id' => $ticket->id,
            'agent_id' => $agent->id,
            'assigned_by' => $agent->id,
            'assigned_at' => now(),
        ]);

        Sanctum::actingAs($agent);

        $response = $this->getJson(
            "/api/tickets/{$ticket->id}"
        );

        $response->assertSuccessful();
    }

    public function test_customer_cannot_view_another_customers_messages(): void
    {
        $roles = $this->createRoles();
        $status = $this->createStatus();

        $customerA = $this->createUser(
            $roles['customer']->id,
            'customer3@test.local',
            'Customer A'
        );

        $customerB = $this->createUser(
            $roles['customer']->id,
            'customer4@test.local',
            'Customer B'
        );

        $ticket = $this->createTicket(
            $customerA,
            $status,
            'Private messages'
        );

        Sanctum::actingAs($customerB);

        $response = $this->getJson(
            "/api/tickets/{$ticket->id}/messages"
        );

        $response->assertForbidden();
    }

    public function test_customer_cannot_add_message_to_another_customers_ticket(): void
    {
        $roles = $this->createRoles();
        $status = $this->createStatus();

        $customerA = $this->createUser(
            $roles['customer']->id,
            'customer5@test.local',
            'Customer A'
        );

        $customerB = $this->createUser(
            $roles['customer']->id,
            'customer6@test.local',
            'Customer B'
        );

        $ticket = $this->createTicket(
            $customerA,
            $status,
            'Protected ticket'
        );

        Sanctum::actingAs($customerB);

        $response = $this->postJson(
            "/api/tickets/{$ticket->id}/messages",
            [
                'message' => 'Unauthorized message.',
            ]
        );

        $response->assertForbidden();
    }

    public function test_admin_can_view_any_ticket(): void
    {
        $roles = $this->createRoles();
        $status = $this->createStatus();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer7@test.local',
            'Customer'
        );

        $admin = $this->createUser(
            $roles['admin']->id,
            'admin@test.local',
            'Admin'
        );

        $ticket = $this->createTicket(
            $customer,
            $status,
            'Admin access test'
        );

        Sanctum::actingAs($admin);

        $response = $this->getJson(
            "/api/tickets/{$ticket->id}"
        );

        $response->assertSuccessful();
    }
}