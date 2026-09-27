<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_owner_can_download_their_document(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $document = Document::create([
            'user_id' => $user->id,
            'name' => 'private-test.txt',
            'path' => 'documents/private-test.txt',
        ]);

        Storage::disk('local')->put(
            'documents/private-test.txt',
            'This is a private document.'
        );

        Sanctum::actingAs($user);

        $response = $this->get(
            "/api/documents/{$document->id}/download"
        );

        $response->assertStatus(200);

        Storage::disk('local')->assertExists(
            'documents/private-test.txt'
        );
    }

    public function test_unauthenticated_user_cannot_download_document(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $document = Document::create([
            'user_id' => $user->id,
            'name' => 'private-test.txt',
            'path' => 'documents/private-test.txt',
        ]);

        Storage::disk('local')->put(
            'documents/private-test.txt',
            'This is a private document.'
        );

        $response = $this->getJson(
            "/api/documents/{$document->id}/download"
        );

        $response->assertStatus(401);
    }

    public function test_another_user_cannot_download_someone_elses_document(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();

        $otherUser = User::factory()->create();

        $document = Document::create([
            'user_id' => $owner->id,
            'name' => 'private-test.txt',
            'path' => 'documents/private-test.txt',
        ]);

        Storage::disk('local')->put(
            'documents/private-test.txt',
            'This is a private document.'
        );

        Sanctum::actingAs($otherUser);

        $response = $this->get(
            "/api/documents/{$document->id}/download"
        );

        $response->assertStatus(403);
    }

    public function test_owner_gets_not_found_when_document_file_does_not_exist(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $document = Document::create([
            'user_id' => $user->id,
            'name' => 'missing.txt',
            'path' => 'documents/missing.txt',
        ]);

        Sanctum::actingAs($user);

        $response = $this->get(
            "/api/documents/{$document->id}/download"
        );

        $response->assertStatus(404);
    }
}