<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TicketAttachmentUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Customer']);
        Role::firstOrCreate(['name' => 'Agent']);
        Role::firstOrCreate(['name' => 'Admin']);

        TicketStatus::firstOrCreate([
            'name' => 'Open',
        ]);
    }

    private function createUser(string $roleName): User
    {
        $user = User::create([
            'role_id' => Role::where('name', $roleName)->firstOrFail()->id,
            'first_name' => 'Test',
            'last_name' => $roleName,
            'email' => fake()->unique()->safeEmail(),
            'password' => 'Password123!',
        ]);

        $user->is_active = true;
        $user->save();

        return $user;
    }

    private function createTicket(User $customer): Ticket
{
    $category = TicketCategory::firstOrCreate([
        'name' => 'Technical Support',
    ]);

    $priority = TicketPriority::firstOrCreate(
        ['name' => 'Medium'],
        ['level' => 2]
    );

    return Ticket::create([
        'ticket_number' => 'TKT-' . fake()->unique()->numerify('########'),
        'customer_id' => $customer->id,
        'category_id' => $category->id,
        'priority_id' => $priority->id,
        'status_id' => TicketStatus::where('name', 'Open')->first()->id,
        'subject' => 'Test ticket',
        'description' => 'Test description',
    ]);
}

    private function token(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    public function test_upload_requires_a_file(): void
    {
        $customer = $this->createUser('Customer');
        $ticket = $this->createTicket($customer);

        $response = $this
            ->withToken($this->token($customer))
            ->postJson("/api/tickets/{$ticket->id}/attachments");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_upload_rejects_forbidden_file_type(): void
    {
        $customer = $this->createUser('Customer');
        $ticket = $this->createTicket($customer);

        $file = UploadedFile::fake()->create(
            'malware.exe',
            100,
            'application/octet-stream'
        );

        $response = $this
        ->withToken($this->token($customer))
        ->postJson("/api/tickets/{$ticket->id}/attachments", [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_upload_rejects_file_larger_than_10_mb(): void
    {
        $customer = $this->createUser('Customer');
        $ticket = $this->createTicket($customer);

        $file = UploadedFile::fake()->create(
            'large.pdf',
            10241,
            'application/pdf'
        );

        $response = $this
        ->withToken($this->token($customer))
        ->postJson("/api/tickets/{$ticket->id}/attachments", [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_upload_accepts_allowed_pdf_file(): void
    {
        $customer = $this->createUser('Customer');
        $ticket = $this->createTicket($customer);

        $file = UploadedFile::fake()->create(
            'document.pdf',
            100,
            'application/pdf'
        );

        $response = $this
            ->withToken($this->token($customer))
            ->post("/api/tickets/{$ticket->id}/attachments", [
                'file' => $file,
            ]);

        $response->assertStatus(201);
    }
}