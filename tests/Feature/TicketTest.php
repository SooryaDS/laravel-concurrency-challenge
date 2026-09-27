<?php

namespace Tests\Feature;

use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_can_be_retrieved_successfully(): void
    {
        $ticket = Ticket::create([
            'title' => 'Test ticket',
            'status' => 'open',
        ]);

        $ticket->version = 1;
        $ticket->save();

        $response = $this->getJson("/api/tickets/{$ticket->id}");

        $response
            ->assertStatus(200)
            ->assertJson([
                'id' => $ticket->id,
                'title' => 'Test ticket',
                'status' => 'open',
                'version' => 1,
            ]);
    }

    public function test_ticket_can_be_updated_successfully(): void
    {
        $ticket = Ticket::create([
            'title' => 'Original title',
            'status' => 'open',
        ]);

        $ticket->version = 1;
        $ticket->save();

        $response = $this->putJson("/api/tickets/{$ticket->id}", [
            'title' => 'Updated title',
            'status' => 'closed',
            'version' => 1,
        ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'id' => $ticket->id,
                'title' => 'Updated title',
                'status' => 'closed',
                'version' => 2,
            ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'title' => 'Updated title',
            'status' => 'closed',
            'version' => 2,
        ]);
    }

    public function test_stale_ticket_version_returns_conflict(): void
    {
        $ticket = Ticket::create([
            'title' => 'Original title',
            'status' => 'open',
        ]);

        $ticket->version = 2;
        $ticket->save();

        $response = $this->putJson("/api/tickets/{$ticket->id}", [
            'title' => 'Stale update',
            'status' => 'closed',
            'version' => 1,
        ]);

        $response
            ->assertStatus(409)
            ->assertJson([
                'message' => 'The ticket has been modified by another request.',
            ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'title' => 'Original title',
            'status' => 'open',
            'version' => 2,
        ]);
    }

    public function test_ticket_update_requires_valid_data(): void
    {
        $ticket = Ticket::create([
            'title' => 'Original title',
            'status' => 'open',
        ]);

        $ticket->version = 1;
        $ticket->save();

        $response = $this->putJson("/api/tickets/{$ticket->id}", [
            'title' => '',
            'status' => '',
            'version' => 0,
        ]);

        $response->assertStatus(422);
    }
}