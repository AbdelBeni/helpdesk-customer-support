<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketAttachment;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketAttachmentAuthorizationTest extends TestCase
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

    private function createTicket(User $customer): Ticket
    {
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
            'subject' => 'Attachment authorization test',
            'description' => 'Testing attachment authorization.',
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

    private function createAttachment(
        Ticket $ticket,
        User $user
    ): TicketAttachment {
        return TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'uploaded_by' => $user->id,
            'original_name' => 'test.txt',
            'file_path' => 'tickets/test/test.txt',
            'mime_type' => 'text/plain',
            'file_size' => 100,
        ]);
    }

    public function test_customer_cannot_view_attachments_of_another_customers_ticket(): void
    {
        Storage::fake('public');

        $roles = $this->createRoles();

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

        $ticket = $this->createTicket($customerA);

        $this->createAttachment($ticket, $customerA);

        Sanctum::actingAs($customerB);

        $response = $this->getJson(
            "/api/tickets/{$ticket->id}/attachments"
        );

        $response->assertForbidden();
    }

    public function test_agent_cannot_view_attachments_of_unassigned_ticket(): void
    {
        Storage::fake('public');

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

        $ticket = $this->createTicket($customer);

        $this->createAttachment($ticket, $customer);

        Sanctum::actingAs($agent);

        $response = $this->getJson(
            "/api/tickets/{$ticket->id}/attachments"
        );

        $response->assertForbidden();
    }

    public function test_agent_can_view_attachments_of_assigned_ticket(): void
    {
        Storage::fake('public');

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

        $ticket = $this->createTicket($customer);

        $this->assignTicket($ticket, $agent);

        $this->createAttachment($ticket, $customer);

        Sanctum::actingAs($agent);

        $response = $this->getJson(
            "/api/tickets/{$ticket->id}/attachments"
        );

        $response->assertSuccessful();
    }

    public function test_customer_cannot_delete_attachment_from_another_customers_ticket(): void
    {
        Storage::fake('public');

        $roles = $this->createRoles();

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

        $ticket = $this->createTicket($customerA);

        $attachment = $this->createAttachment(
            $ticket,
            $customerA
        );

        Sanctum::actingAs($customerB);

        $response = $this->deleteJson(
            "/api/tickets/{$ticket->id}/attachments/{$attachment->id}"
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('ticket_attachments', [
            'id' => $attachment->id,
        ]);
    }

    public function test_agent_cannot_delete_attachment_from_unassigned_ticket(): void
    {
        Storage::fake('public');

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

        $attachment = $this->createAttachment(
            $ticket,
            $customer
        );

        Sanctum::actingAs($agent);

        $response = $this->deleteJson(
            "/api/tickets/{$ticket->id}/attachments/{$attachment->id}"
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('ticket_attachments', [
            'id' => $attachment->id,
        ]);
    }

    public function test_customer_can_view_attachments_of_own_ticket(): void
    {
        Storage::fake('public');

        $roles = $this->createRoles();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer6@test.local',
            'Customer'
        );

        $ticket = $this->createTicket($customer);

        $this->createAttachment($ticket, $customer);

        Sanctum::actingAs($customer);

        $response = $this->getJson(
            "/api/tickets/{$ticket->id}/attachments"
        );

        $response->assertSuccessful();
    }
}