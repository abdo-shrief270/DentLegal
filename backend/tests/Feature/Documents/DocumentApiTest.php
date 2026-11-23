<?php

namespace Tests\Feature\Documents;

use App\Modules\Cases\Enums\CaseStatus;
use App\Modules\Cases\Models\DentalCase;
use App\Modules\Documents\DocumentVault;
use App\Modules\Documents\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

class DocumentApiTest extends TestCase
{
    private const PDF = "%PDF-1.7\n% synthetic test document\n1 0 obj << >> endobj\n%%EOF";

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
    }

    private function upload(DentalCase $case, string $contents = self::PDF, string $name = 'letter.pdf', string $kind = 'complaint_letter'): TestResponse
    {
        return $this->post("/api/v1/cases/{$case->id}/documents", ['file' => UploadedFile::fake()->createWithContent($name, $contents), 'kind' => $kind]);
    }

    public function test_upload_stores_the_file_encrypted(): void
    {
        $me = $this->actingAsDentist();
        $case = DentalCase::factory()->for($me, 'dentist')->create();

        $id = $this->upload($case)->assertCreated()
            ->assertJsonPath('data.kind', 'complaint_letter')
            ->assertJsonPath('data.mime', 'application/pdf')
            ->assertJsonPath('data.encrypted', true)
            ->json('data.id');

        $doc = Document::query()->findOrFail($id);
        $onDisk = Storage::disk('documents')->get($doc->storage_key);
        $this->assertStringNotContainsString('synthetic test document', $onDisk);
        $this->assertSame(self::PDF, app(DocumentVault::class)->read($doc->id, $doc->storage_key, $doc->wrapped_key));
        $this->assertSame(hash('sha256', self::PDF), $doc->sha256);
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.uploaded', 'subject_id' => $id]);
        $this->getJson("/api/v1/cases/{$case->id}")->assertJsonCount(1, 'data.documents');
    }

    public function test_a_file_bound_to_one_document_cannot_be_read_as_another(): void
    {
        $vault = app(DocumentVault::class);
        $stored = $vault->store('00000000-0000-7000-8000-000000000001', 'secret bytes');

        $this->expectException(RuntimeException::class);
        $vault->read('00000000-0000-7000-8000-000000000002', $stored['storage_key'], $stored['wrapped_key']);
    }

    public function test_files_are_checked_by_their_bytes(): void
    {
        $me = $this->actingAsDentist();
        $case = DentalCase::factory()->for($me, 'dentist')->create();

        $this->upload($case, "PK\x03\x04 a zip renamed to pdf", 'renamed.pdf')
            ->assertStatus(415)
            ->assertJsonPath('error.code', 'unsupported_media_type');
    }

    public function test_documents_are_locked_once_analysis_starts(): void
    {
        $me = $this->actingAsDentist();
        $case = DentalCase::factory()->for($me, 'dentist')->status(CaseStatus::Analyzing)->create();

        $this->upload($case)->assertStatus(409)->assertJsonPath('error.code', 'invalid_state');
    }

    public function test_other_dentists_cannot_upload(): void
    {
        $case = DentalCase::factory()->create();
        $this->actingAsDentist();

        $this->upload($case)->assertNotFound();
    }

    public function test_delete_removes_the_row_and_the_file(): void
    {
        $me = $this->actingAsDentist();
        $case = DentalCase::factory()->for($me, 'dentist')->create();
        $id = $this->upload($case)->json('data.id');
        $key = Document::query()->findOrFail($id)->storage_key;

        $this->deleteJson("/api/v1/cases/{$case->id}/documents/{$id}")->assertNoContent();

        $this->assertDatabaseMissing('documents', ['id' => $id]);
        Storage::disk('documents')->assertMissing($key);
    }
}
