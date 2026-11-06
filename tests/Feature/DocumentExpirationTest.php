<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_set_and_clear_an_expiration_date(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->createDocument($employee);

        $this->actingAs($employee)->get(route('documents.edit', $document))
            ->assertOk()
            ->assertSee('Expiration date');

        $this->put(route('documents.update', $document), [
            'expiration_date' => now()->addDays(10)->toDateString(),
        ])->assertRedirect(route('documents.show', $document));

        $this->assertSame(now()->addDays(10)->toDateString(), $document->fresh()->expiration_date->toDateString());
        $this->get(route('documents.show', $document))
            ->assertOk()
            ->assertSee('Expiration date')
            ->assertSee('expires in 10 day(s)');

        $this->put(route('documents.update', $document), ['expiration_date' => ''])
            ->assertRedirect(route('documents.show', $document));
        $this->assertNull($document->fresh()->expiration_date);
    }

    public function test_document_edit_validation_errors_are_programmatically_associated_with_fields(): void
    {
        $this->seed();
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $document = $this->createDocument($employee);

        $this->actingAs($employee)
            ->from(route('documents.edit', $document))
            ->put(route('documents.update', $document), ['title' => str_repeat('x', 256)])
            ->assertSessionHasErrors('title');

        $this->get(route('documents.edit', $document))
            ->assertOk()
            ->assertSee('aria-describedby="titleHelp titleError"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('id="titleError" class="invalid-feedback d-block"', false);
    }

    public function test_dashboard_lists_expired_and_upcoming_owned_documents_only(): void
    {
        $this->seed();
        $this->travelTo(now()->setDate(2026, 11, 5)->startOfDay());
        $employee = User::where('email', 'employee@panelco3.local')->firstOrFail();
        $otherEmployee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $expired = $this->createDocument($employee, [
            'title' => 'Expired maintenance permit',
            'expiration_date' => now()->subDay()->toDateString(),
        ]);
        $upcoming = $this->createDocument($employee, [
            'title' => 'Upcoming safety certification',
            'expiration_date' => now()->addDays(12)->toDateString(),
        ]);
        $this->createDocument($employee, [
            'title' => 'Long-term policy',
            'expiration_date' => now()->addDays(31)->toDateString(),
        ]);
        $this->createDocument($otherEmployee, [
            'title' => 'Private expiring file',
            'expiration_date' => now()->addDays(2)->toDateString(),
        ]);

        $response = $this->actingAs($employee)->get(route('dashboard'));
        $response->assertOk()
            ->assertSee('Expiring documents')
            ->assertSee('Expired maintenance permit')
            ->assertSee('Upcoming safety certification')
            ->assertDontSee('Private expiring file')
            ->assertSee('Expired')
            ->assertSee('12d');

        $this->assertSame(
            [$expired->id, $upcoming->id],
            $response->viewData('expiringDocuments')->pluck('id')->all()
        );
        $this->travelBack();
    }

    private function createDocument(User $uploader, array $attributes = []): Document
    {
        return Document::create(array_merge([
            'uploaded_by' => $uploader->id,
            'title' => 'Expiration test document',
            'original_filename' => 'expiration-test.txt',
            'storage_path' => 'documents/expiration-test.txt',
            'mime_type' => 'text/plain',
            'file_size' => 64,
            'processing_status' => 'completed',
        ], $attributes));
    }
}
