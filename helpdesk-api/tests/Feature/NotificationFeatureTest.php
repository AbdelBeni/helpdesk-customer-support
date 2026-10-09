<?php

namespace Tests\Feature;

use App\Models\Notification;
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

class NotificationFeatureTest extends TestCase
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
            'tickets.update',
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
                [
                    'role_id' => $customerRole->id,
                    'permission_id' => $permission->id,
                ],
            ]);
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

    private function createStatuses(): array
    {
        $open = TicketStatus::create([
            'name' => 'Open',
            'is_closed' => false,
        ]);

        $inProgress = TicketStatus::create([
            'name' => 'In Progress',
            'is_closed' => false,
        ]);

        $resolved = TicketStatus::create([
            'name' => 'Resolved',
            'is_closed' => false,
        ]);

        return [
            'open' => $open,
            'in_progress' => $inProgress,
            'resolved' => $resolved,
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
            'ticket_number' => 'TKT-' . strtoupper(
                substr(md5(uniqid()), 0, 8)
            ),
            'customer_id' => $customer->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'subject' => 'Notification test',
            'description' => 'Testing ticket notifications.',
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

    public function test_customer_reply_notifies_assigned_agent(): void
    {
        $roles = $this->createRoles();
        $statuses = $this->createStatuses();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer1@test.local',
            'Customer'
        );

        $agent = $this->createUser(
            $roles['agent']->id,
            'agent1@test.local',
            'Agent'
        );

        $ticket = $this->createTicket(
            $customer,
            $statuses['open']
        );

        $this->assignTicket($ticket, $agent);

        Sanctum::actingAs($customer);

        $response = $this->postJson(
            "/api/tickets/{$ticket->id}/messages",
            [
                'message' => 'I still need help.',
            ]
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $agent->id,
            'type' => 'ticket_message',
            'title' => 'New customer reply',
        ]);
    }

    public function test_agent_reply_notifies_customer(): void
    {
        $roles = $this->createRoles();
        $statuses = $this->createStatuses();

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
            $statuses['in_progress']
        );

        $this->assignTicket($ticket, $agent);

        Sanctum::actingAs($agent);

        $response = $this->postJson(
            "/api/tickets/{$ticket->id}/messages",
            [
                'message' => 'I am checking your issue.',
            ]
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $customer->id,
            'type' => 'ticket_message',
            'title' => 'New reply on your ticket',
        ]);
    }

    public function test_status_change_notifies_customer(): void
    {
        $roles = $this->createRoles();
        $statuses = $this->createStatuses();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer3@test.local',
            'Customer'
        );

        $agent = $this->createUser(
            $roles['agent']->id,
            'agent3@test.local',
            'Agent'
        );

        $ticket = $this->createTicket(
            $customer,
            $statuses['open']
        );

        $this->assignTicket($ticket, $agent);

        Sanctum::actingAs($agent);

        $response = $this->patchJson(
            "/api/tickets/{$ticket->id}/status",
            [
                'status_id' => $statuses['in_progress']->id,
            ]
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $customer->id,
            'type' => 'ticket_status_changed',
            'title' => 'Ticket status updated',
        ]);
    }

    public function test_resolved_ticket_notifies_customer(): void
    {
        $roles = $this->createRoles();
        $statuses = $this->createStatuses();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer4@test.local',
            'Customer'
        );

        $agent = $this->createUser(
            $roles['agent']->id,
            'agent4@test.local',
            'Agent'
        );

        $ticket = $this->createTicket(
            $customer,
            $statuses['in_progress']
        );

        $this->assignTicket($ticket, $agent);

        Sanctum::actingAs($agent);

        $response = $this->patchJson(
            "/api/tickets/{$ticket->id}/status",
            [
                'status_id' => $statuses['resolved']->id,
            ]
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $customer->id,
            'type' => 'ticket_resolved',
            'title' => 'Ticket resolved',
        ]);
    }

    public function test_reopened_ticket_notifies_customer(): void
    {
        $roles = $this->createRoles();

        $open = TicketStatus::create([
            'name' => 'Open',
            'is_closed' => false,
        ]);

        $resolved = TicketStatus::create([
            'name' => 'Resolved',
            'is_closed' => false,
        ]);

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer5@test.local',
            'Customer'
        );

        $admin = $this->createUser(
            $roles['admin']->id,
            'admin5@test.local',
            'Admin'
        );

        $ticket = $this->createTicket(
            $customer,
            $resolved
        );

        $ticket->update([
            'resolved_at' => now()->subHour(),
        ]);

        Sanctum::actingAs($admin);

        $response = $this->patchJson(
            "/api/tickets/{$ticket->id}/status",
            [
                'status_id' => $open->id,
            ]
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $customer->id,
            'type' => 'ticket_reopened',
            'title' => 'Ticket reopened',
        ]);
    }
}