<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_folder_cards_open_a_folder_with_all_its_documents(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $engineering = DocumentCategory::create(['name' => 'Engineering']);
        $finance = DocumentCategory::create(['name' => 'Finance']);

        $this->createDocument($user, 'Assigned memo', ['category_id' => $engineering->id]);
        $this->createDocument($user, 'Suggested memo', ['suggested_category_id' => $engineering->id]);
        $this->createDocument($user, 'Finance memo', ['category_id' => $finance->id]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('folders.show', $engineering).'"', false)
            ->assertSee('href="'.route('folders.show', $finance).'"', false);

        $response = $this->get(route('folders.show', $engineering))
            ->assertOk()
            ->assertSeeText('Engineering')
            ->assertSeeText('Assigned memo')
            ->assertSeeText('Suggested memo')
            ->assertDontSeeText('Finance memo')
            ->assertDontSee('id="libFolderBanner"', false);

        $this->assertSame(2, $response->viewData('documents')->total());
    }

    public function test_library_folder_grid_lists_every_folder(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $folders = collect();

        foreach (range(1, 6) as $number) {
            $folders->push(DocumentCategory::create(['name' => 'Folder '.$number]));
        }

        $this->createDocument($user, 'Grid document', ['category_id' => $folders->first()->id]);

        $html = $this->actingAs($user)->get(route('documents.library'))
            ->assertOk()
            ->getContent();

        $html = (string) $html;
        foreach ($folders as $folder) {
            $this->assertStringContainsString('href="'.route('folders.show', $folder).'"', $html);
        }

        $this->assertStringContainsString('grid-auto-flow: column', $html);
        $this->assertStringContainsString('overflow-y: hidden', $html);
        $this->assertStringContainsString('class="lib-dept-slider-btn" data-target=".lib-dept-grid" data-dir="-1"', $html);
        $this->assertStringContainsString('class="lib-dept-slider-btn" data-target=".lib-dept-grid" data-dir="1"', $html);
    }

    private function createDocument(User $uploader, string $title, array $attributes = []): Document
    {
        return Document::create(array_merge([
            'uploaded_by' => $uploader->id,
            'title' => $title,
            'original_filename' => $title.'.txt',
            'storage_path' => 'documents/'.str_replace(' ', '-', strtolower($title)).'.txt',
            'mime_type' => 'text/plain',
            'file_size' => 100,
            'processing_status' => 'completed',
        ], $attributes));
    }
}
