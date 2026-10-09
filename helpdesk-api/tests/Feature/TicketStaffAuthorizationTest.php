<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketActivityLog;
use App\Models\TicketCategory;
use App\Models\TicketInternalNote;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketStaffAuthorizationTest extends TestCase
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

        $permissions = [
            'tickets.view',
            'tickets.internal_notes',
        ];

        foreach ($permissions as $name) {
            $permission = Permission::create([
                'name' => $name,
                'description' => $name,
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
            ]);

            if ($name === 'tickets.view') {
                DB::table('role_permissions')->insert([
                    'role_id' => $customerRole->id,
                    'permission_id' => $permission->id,
                ]);
            }
        }

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

    private function createTicket(
        User $customer
    ): Ticket {
        $status = TicketStatus::create([
            'name' => 'Open',
            'is_closed' => false,
        ]);

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
            'subject' => 'Staff authorization test',
            'description' => 'Testing staff-only authorization.',
        ]);
    }

    private function assignTicket(
        Ticket $ticket,
        User $agent
    ): void {
        TicketAssignment::create([
            'ticket_id' => $ticket->id,
            'agent_id' => $agent->id,
            'assigned_by' => $agent->id,
            'assigned_at' => now(),
        ]);
    }

    public function test_customer_cannot_view_internal_notes(): void
    {
        $roles = $this->createRoles();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer@test.local',
            'Customer'
        );

        $ticket = $this->createTicket($customer);

        TicketInternalNote::create([
            'ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'content' => 'Private staff note.',
        ]);

        Sanctum::actingAs($customer);

        $response = $this->getJson(
            "/api/tickets/{$ticket->id}/internal-notes"
        );

        $response->assertForbidden();
    }

    public function test_agent_can_view_internal_notes_of_assigned_ticket(): void
    {
        $roles = $this->createRoles();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer2@test.local',
            'Customer'
        );

        $agent = $this->createUser(
            $roles['agent']->id,
            'agent@test.local',
            'Agent'
        );

        $ticket = $this->createTicket($customer);

        $this->assignTicket($ticket, $agent);

        TicketInternalNote::create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'content' => 'Agent internal note.',
        ]);

        Sanctum::actingAs($agent);

        $response = $this->getJson(
            "/api/tickets/{$ticket->id}/internal-notes"
        );

        $response->assertSuccessful();
    }

    public function test_agent_cannot_view_internal_notes_of_unassigned_ticket(): void
    {
        $roles = $this->createRoles();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer3@test.local',
            'Customer'
        );

        $agent = $this->createUser(
            $roles['agent']->id,
            'agent2@test.local',
            'Agent'
        );

        $ticket = $this->createTicket($customer);

        Sanctum::actingAs($agent);

        $response = $this->getJson(
            "/api/tickets/{$ticket->id}/internal-notes"
        );

        $response->assertForbidden();
    }

    public function test_customer_cannot_view_activity_logs(): void
    {
        $roles = $this->createRoles();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer4@test.local',
            'Customer'
        );

        $ticket = $this->createTicket($customer);

        Sanctum::actingAs($customer);

        $response = $this->getJson(
            "/api/tickets/{$ticket->id}/activity-logs"
        );

        $response->assertForbidden();
    }

    public function test_agent_can_view_activity_logs_of_assigned_ticket(): void
    {
        $roles = $this->createRoles();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer5@test.local',
            'Customer'
        );

        $agent = $this->createUser(
            $roles['agent']->id,
            'agent3@test.local',
            'Agent'
        );

        $ticket = $this->createTicket($customer);

        $this->assignTicket($ticket, $agent);

        TicketActivityLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'action' => 'test',
        ]);

        Sanctum::actingAs($agent);

        $response = $this->getJson(
            "/api/tickets/{$ticket->id}/activity-logs"
        );

        $response->assertSuccessful();
    }

    public function test_agent_cannot_view_activity_logs_of_unassigned_ticket(): void
    {
        $roles = $this->createRoles();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer6@test.local',
            'Customer'
        );

        $agent = $this->createUser(
            $roles['agent']->id,
            'agent4@test.local',
            'Agent'
        );

        $ticket = $this->createTicket($customer);

        Sanctum::actingAs($agent);

        $response = $this->getJson(
            "/api/tickets/{$ticket->id}/activity-logs"
        );

        $response->assertForbidden();
    }
}