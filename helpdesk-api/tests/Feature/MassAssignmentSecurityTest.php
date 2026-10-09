<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MassAssignmentSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function createRoles(): array
    {
        $adminRole = Role::create([
            'name' => 'Admin',
        ]);

        $customerRole = Role::create([
            'name' => 'Customer',
        ]);

        $createPermission = Permission::create([
            'name' => 'tickets.create',
            'description' => 'Create tickets',
        ]);

        $updatePermission = Permission::create([
            'name' => 'tickets.update',
            'description' => 'Update tickets',
        ]);

        DB::table('role_permissions')->insert([
            [
                'role_id' => $adminRole->id,
                'permission_id' => $createPermission->id,
            ],
            [
                'role_id' => $customerRole->id,
                'permission_id' => $createPermission->id,
            ],
            [
                'role_id' => $adminRole->id,
                'permission_id' => $updatePermission->id,
            ],
        ]);

        return [
            'admin' => $adminRole,
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

    private function createStatus(string $name = 'Open'): TicketStatus
    {
        return TicketStatus::create([
            'name' => $name,
            'is_closed' => $name === 'Closed',
        ]);
    }

    private function createCategory(): TicketCategory
    {
        return TicketCategory::create([
            'name' => 'Technical Support',
        ]);
    }

    private function createPriority(): TicketPriority
    {
        return TicketPriority::create([
            'name' => 'Medium',
            'level' => 2,
        ]);
    }

    private function createTicket(
        User $customer,
        TicketStatus $status
    ): Ticket {
        return Ticket::create([
            'ticket_number' => 'TKT-ORIGINAL',
            'customer_id' => $customer->id,
            'category_id' => $this->createCategory()->id,
            'priority_id' => $this->createPriority()->id,
            'status_id' => $status->id,
            'subject' => 'Original subject',
            'description' => 'Original description',
        ]);
    }

    public function test_customer_cannot_set_protected_fields_when_creating_ticket(): void
    {
        $roles = $this->createRoles();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer-create@test.local',
            'Customer'
        );

        $attacker = $this->createUser(
            $roles['customer']->id,
            'attacker-create@test.local',
            'Attacker'
        );

        $openStatus = $this->createStatus('Open');
        $closedStatus = $this->createStatus('Closed');

        $category = $this->createCategory();
        $priority = $this->createPriority();

        Sanctum::actingAs($customer);

        $response = $this->postJson('/api/tickets', [
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'subject' => 'Mass assignment attack',
            'description' => 'Trying to modify protected fields.',
            'customer_id' => $attacker->id,
            'status_id' => $closedStatus->id,
            'first_response_at' => now()->toDateTimeString(),
            'resolved_at' => now()->toDateTimeString(),
            'closed_at' => now()->toDateTimeString(),
            'ticket_number' => 'TKT-HACKED',
        ]);

        $response->assertSuccessful();

        $ticket = Ticket::latest('id')->first();

        $this->assertSame(
            $customer->id,
            $ticket->customer_id
        );

        $this->assertSame(
            $openStatus->id,
            $ticket->status_id
        );

        $this->assertNull($ticket->first_response_at);
        $this->assertNull($ticket->resolved_at);
        $this->assertNull($ticket->closed_at);

        $this->assertNotSame(
            'TKT-HACKED',
            $ticket->ticket_number
        );
    }

    public function test_admin_cannot_update_protected_ticket_fields(): void
    {
        $roles = $this->createRoles();

        $admin = $this->createUser(
            $roles['admin']->id,
            'admin-update@test.local',
            'Admin'
        );

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer-update@test.local',
            'Customer'
        );

        $openStatus = $this->createStatus('Open');
        $closedStatus = $this->createStatus('Closed');

        $ticket = $this->createTicket(
            $customer,
            $openStatus
        );

        Sanctum::actingAs($admin);

        $response = $this->patchJson(
            "/api/tickets/{$ticket->id}",
            [
                'subject' => 'Updated subject',
                'customer_id' => $admin->id,
                'status_id' => $closedStatus->id,
                'first_response_at' => now()->toDateTimeString(),
                'resolved_at' => now()->toDateTimeString(),
                'closed_at' => now()->toDateTimeString(),
                'ticket_number' => 'TKT-HACKED',
            ]
        );

        $response->assertSuccessful();

        $ticket->refresh();

        $this->assertSame(
            $customer->id,
            $ticket->customer_id
        );

        $this->assertSame(
            $openStatus->id,
            $ticket->status_id
        );

        $this->assertSame(
            'Updated subject',
            $ticket->subject
        );

        $this->assertNull($ticket->first_response_at);
        $this->assertNull($ticket->resolved_at);
        $this->assertNull($ticket->closed_at);

        $this->assertNotSame(
            'TKT-HACKED',
            $ticket->ticket_number
        );
    }
}