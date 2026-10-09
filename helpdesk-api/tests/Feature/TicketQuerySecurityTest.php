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

class TicketQuerySecurityTest extends TestCase
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

    private function createStatus(string $name = 'Open'): TicketStatus
    {
        return TicketStatus::create([
            'name' => $name,
            'is_closed' => $name === 'Closed',
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
            'description' => 'Query security test ticket.',
        ]);
    }

    public function test_invalid_sort_falls_back_to_created_at_desc(): void
    {
        $roles = $this->createRoles();
        $status = $this->createStatus();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer-sort@test.local',
            'Customer'
        );

        $ticket = $this->createTicket(
            $customer,
            $status,
            'Sort security test'
        );

        Sanctum::actingAs($customer);

        $response = $this->getJson(
            '/api/tickets?sort=invalid_column'
        );

        $response->assertSuccessful();
        $response->assertJsonPath('data.0.id', $ticket->id);
    }

    public function test_per_page_cannot_exceed_100(): void
    {
        $roles = $this->createRoles();
        $status = $this->createStatus();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer-per-page@test.local',
            'Customer'
        );

        $this->createTicket(
            $customer,
            $status,
            'Per page security test'
        );

        Sanctum::actingAs($customer);

        $response = $this->getJson(
            '/api/tickets?per_page=1000'
        );

        $response->assertSuccessful();
        $response->assertJsonPath('meta.per_page', 100);
    }

    public function test_per_page_cannot_be_less_than_1(): void
    {
        $roles = $this->createRoles();
        $status = $this->createStatus();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer-per-page-min@test.local',
            'Customer'
        );

        $this->createTicket(
            $customer,
            $status,
            'Per page minimum test'
        );

        Sanctum::actingAs($customer);

        $response = $this->getJson(
            '/api/tickets?per_page=0'
        );

        $response->assertSuccessful();
        $response->assertJsonPath('meta.per_page', 1);
    }

    public function test_valid_sort_is_accepted(): void
    {
        $roles = $this->createRoles();
        $status = $this->createStatus();

        $customer = $this->createUser(
            $roles['customer']->id,
            'customer-valid-sort@test.local',
            'Customer'
        );

        $this->createTicket(
            $customer,
            $status,
            'Valid sort test'
        );

        Sanctum::actingAs($customer);

        $response = $this->getJson(
            '/api/tickets?sort=-created_at'
        );

        $response->assertSuccessful();
    }
}