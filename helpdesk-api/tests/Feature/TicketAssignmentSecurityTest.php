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

class TicketAssignmentSecurityTest extends TestCase
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

        $assignPermission = Permission::create([
            'name' => 'tickets.assign',
            'description' => 'Assign tickets',
        ]);

        $viewPermission = Permission::create([
            'name' => 'tickets.view',
            'description' => 'View tickets',
        ]);

        DB::table('role_permissions')->insert([
            [
                'role_id' => $adminRole->id,
                'permission_id' => $assignPermission->id,
            ],
            [
                'role_id' => $agentRole->id,
                'permission_id' => $assignPermission->id,
            ],
            [
                'role_id' => $adminRole->id,
                'permission_id' => $viewPermission->id,
            ],
            [
                'role_id' => $agentRole->id,
                'permission_id' => $viewPermission->id,
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
        string $firstName,
        bool $isActive = true
    ): User {
        $user = User::create([
            'role_id' => $roleId,
            'first_name' => $firstName,
            'last_name' => 'User',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);

        $user->is_active = $isActive;
        $user->save();

        return $user;
    }

    private function createStatuses(): array
    {
        return [
            'open' => TicketStatus::create([
                'name' => 'Open',
                'is_closed' => false,
            ]),
            'in_progress' => TicketStatus::create([
                'name' => 'In Progress',
                'is_closed' => false,
            ]),
        ];
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
            'ticket_number' => 'TKT-' . strtoupper(substr(md5(uniqid()), 0, 8)),
            'customer_id' => $customer->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'subject' => 'Assignment security test',
            'description' => 'Assignment authorization test ticket.',
        ]);
    }

    public function test_customer_cannot_assign_ticket(): void
    {
        $roles = $this->createRoles();

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

        $statuses = $this->createStatuses();

        $ticket = $this->createTicket(
            $customer,
            $statuses['open']
        );

        Sanctum::actingAs($customer);

        $response = $this->postJson(
            "/api/tickets/{$ticket->id}/assign",
            [
                'agent_id' => $agent->id,
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseMissing('ticket_assignments', [
            'ticket_id' => $ticket->id,
            'agent_id' => $agent->id,
            'unassigned_at' => null,
        ]);
    }

    public function test_agent_can_assign_ticket_to_another_agent(): void
    {
        $roles = $this->createRoles();

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

        $otherAgent = $this->createUser(
            $roles['agent']->id,
            'agent3@test.local',
            'OtherAgent'
        );

        $statuses = $this->createStatuses();

        $ticket = $this->createTicket(
            $customer,
            $statuses['open']
        );

        Sanctum::actingAs($agent);

        $response = $this->postJson(
            "/api/tickets/{$ticket->id}/assign",
            [
                'agent_id' => $otherAgent->id,
            ]
        );

        $response->assertCreated();

        $this->assertDatabaseHas('ticket_assignments', [
            'ticket_id' => $ticket->id,
            'agent_id' => $otherAgent->id,
            'assigned_by' => $agent->id,
            'unassigned_at' => null,
        ]);
    }

    public function test_agent_can_claim_unassigned_ticket(): void
    {
        $roles = $this->createRoles();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer3@test.local',
            'Customer'
        );

        $agent = $this->createUser(
            $roles['agent']->id,
            'agent4@test.local',
            'Agent'
        );

        $statuses = $this->createStatuses();

        $ticket = $this->createTicket(
            $customer,
            $statuses['open']
        );

        Sanctum::actingAs($agent);

        $response = $this->postJson(
            "/api/tickets/{$ticket->id}/claim"
        );

        $response->assertCreated();

        $this->assertDatabaseHas('ticket_assignments', [
            'ticket_id' => $ticket->id,
            'agent_id' => $agent->id,
            'assigned_by' => $agent->id,
            'unassigned_at' => null,
        ]);

        $ticket->refresh();

        $this->assertSame(
            $statuses['in_progress']->id,
            $ticket->status_id
        );
    }

    public function test_customer_cannot_claim_ticket(): void
    {
        $roles = $this->createRoles();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer4@test.local',
            'Customer'
        );

        $statuses = $this->createStatuses();

        $ticket = $this->createTicket(
            $customer,
            $statuses['open']
        );

        Sanctum::actingAs($customer);

        $response = $this->postJson(
            "/api/tickets/{$ticket->id}/claim"
        );

        $response->assertForbidden();

        $this->assertDatabaseMissing('ticket_assignments', [
            'ticket_id' => $ticket->id,
            'unassigned_at' => null,
        ]);
    }

    public function test_agent_cannot_claim_already_assigned_ticket(): void
    {
        $roles = $this->createRoles();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer5@test.local',
            'Customer'
        );

        $agent = $this->createUser(
            $roles['agent']->id,
            'agent5@test.local',
            'Agent'
        );

        $otherAgent = $this->createUser(
            $roles['agent']->id,
            'agent6@test.local',
            'OtherAgent'
        );

        $statuses = $this->createStatuses();

        $ticket = $this->createTicket(
            $customer,
            $statuses['open']
        );

        TicketAssignment::create([
            'ticket_id' => $ticket->id,
            'agent_id' => $otherAgent->id,
            'assigned_by' => $otherAgent->id,
            'assigned_at' => now(),
        ]);

        Sanctum::actingAs($agent);

        $response = $this->postJson(
            "/api/tickets/{$ticket->id}/claim"
        );

        $response->assertStatus(422);

        $this->assertDatabaseMissing('ticket_assignments', [
            'ticket_id' => $ticket->id,
            'agent_id' => $agent->id,
            'unassigned_at' => null,
        ]);
    }

    public function test_admin_can_assign_ticket(): void
    {
        $roles = $this->createRoles();

        $admin = $this->createUser(
            $roles['admin']->id,
            'admin@test.local',
            'Admin'
        );

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer6@test.local',
            'Customer'
        );

        $agent = $this->createUser(
            $roles['agent']->id,
            'agent7@test.local',
            'Agent'
        );

        $statuses = $this->createStatuses();

        $ticket = $this->createTicket(
            $customer,
            $statuses['open']
        );

        Sanctum::actingAs($admin);

        $response = $this->postJson(
            "/api/tickets/{$ticket->id}/assign",
            [
                'agent_id' => $agent->id,
            ]
        );

        $response->assertCreated();

        $this->assertDatabaseHas('ticket_assignments', [
            'ticket_id' => $ticket->id,
            'agent_id' => $agent->id,
            'assigned_by' => $admin->id,
            'unassigned_at' => null,
        ]);
    }
}