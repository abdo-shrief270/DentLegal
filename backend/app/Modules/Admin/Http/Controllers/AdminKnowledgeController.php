<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Identity\Models\User;
use App\Modules\Knowledge\Actions\ChangeVersionLifecycle;
use App\Modules\Knowledge\Actions\PublishSourceVersion;
use App\Modules\Knowledge\Actions\UploadSourceVersion;
use App\Modules\Knowledge\Models\KnowledgeChunk;
use App\Modules\Knowledge\Models\KnowledgeSource;
use App\Modules\Knowledge\Models\KnowledgeSourceVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/** «قاعدة المعرفة» (PROJECT1_PLAN.md S1.5, S7.3): sources, versions, chunk preview and the publish lifecycle. */
final class AdminKnowledgeController
{
    public function sources(): JsonResponse
    {
        $sources = KnowledgeSource::query()->with(['versions' => fn ($q) => $q->orderByDesc('created_at')])->orderBy('title_ar')->get();

        return response()->json(['data' => $sources->map(fn (KnowledgeSource $s): array => self::source($s))->values()]);
    }

    /** {source} is a source id, or `new` with `new_source_title` (and optionally `new_source_type`). */
    public function upload(Request $request, string $source, UploadSourceVersion $upload): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:'.((int) config('dentlegal.knowledge.max_mb') * 1024)],
            'edition' => ['required', 'string', 'min:2', 'max:120'],
            'effective_from' => ['nullable', 'date'],
            'extraction_method' => ['required', 'in:auto,text_layer,ocr,llm_transcription'],
            'new_source_title' => [$source === 'new' ? 'required' : 'prohibited', 'nullable', 'string', 'min:3', 'max:200'],
            'new_source_type' => ['nullable', 'in:law,regulation,job_description,guideline'],
        ]);
        if ($source !== 'new') {
            KnowledgeSource::query()->findOrFail($source);
        }
        /** @var UploadedFile $file */
        $file = $request->file('file');
        $version = $upload($this->admin($request), $file, [...$data, 'source_id' => $source === 'new' ? null : $source]);

        return response()->json(['data' => self::versionRow($version)], 202);
    }

    public function version(KnowledgeSourceVersion $version): JsonResponse
    {
        $version->load('source');

        return response()->json(['data' => [
            'source' => self::source($version->source->load('versions')),
            'version' => [...self::versionRow($version), 'report' => $version->ingest_report, 'error' => $version->error],
        ]]);
    }

    public function chunks(Request $request, KnowledgeSourceVersion $version): JsonResponse
    {
        $page = KnowledgeChunk::query()->where('version_id', $version->id)->orderBy('ordinal')
            ->select(['id', 'chunk_key', 'chunk_type', 'article_no', 'paragraph_ref', 'page_from', 'page_to', 'token_count', 'display_text'])
            ->cursorPaginate(min(200, max(10, (int) $request->query('per_page', 100))));

        return response()->json([
            'data' => collect($page->items())->map(fn (KnowledgeChunk $c): array => [
                'id' => $c->id, 'chunk_key' => $c->chunk_key, 'chunk_type' => $c->chunk_type, 'article_no' => $c->article_no,
                'paragraph_ref' => $c->paragraph_ref, 'label' => $c->label(),
                'pages' => $c->getAttribute('page_from') === $c->getAttribute('page_to') ? (string) $c->getAttribute('page_from') : $c->getAttribute('page_from').'–'.$c->getAttribute('page_to'),
                'tokens' => (int) $c->getAttribute('token_count'), 'text' => $c->display_text,
            ]),
            'meta' => ['next_cursor' => $page->nextCursor()?->encode()],
        ]);
    }

    public function publish(Request $request, KnowledgeSourceVersion $version, PublishSourceVersion $publish): JsonResponse
    {
        return response()->json(['data' => self::versionRow($publish($version, $this->admin($request)))]);
    }

    public function archive(Request $request, KnowledgeSourceVersion $version, ChangeVersionLifecycle $lifecycle): JsonResponse
    {
        return response()->json(['data' => self::versionRow($lifecycle->archive($this->admin($request), $version))]);
    }

    public function reindex(Request $request, KnowledgeSourceVersion $version, ChangeVersionLifecycle $lifecycle): JsonResponse
    {
        $data = $request->validate(['extraction_method' => ['nullable', 'in:auto,text_layer,ocr,llm_transcription']]);

        return response()->json(['data' => self::versionRow($lifecycle->reindex($this->admin($request), $version, $data['extraction_method'] ?? null))], 202);
    }

    /** @return array<string, mixed> */
    private static function source(KnowledgeSource $s): array
    {
        return [
            'id' => $s->id, 'code' => $s->code, 'title' => $s->title_ar, 'type' => $s->type->value,
            'versions' => $s->versions->map(fn (KnowledgeSourceVersion $v): array => self::versionRow($v))->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    private static function versionRow(KnowledgeSourceVersion $v): array
    {
        return [
            'id' => $v->id, 'source_id' => $v->source_id, 'version_label' => $v->version_label, 'edition' => $v->edition,
            'effective_from' => $v->effective_from?->toDateString(), 'file_name' => $v->file_name,
            'extraction_method' => $v->extraction_method ?? $v->requested_method, 'status' => $v->status->value, 'chunk_count' => $v->chunk_count,
            'published_at' => $v->published_at?->toIso8601ZuluString(), 'updated_at' => $v->updated_at->toIso8601ZuluString(),
            'warnings' => $v->ingest_report['warnings'] ?? null,
        ];
    }

    private function admin(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
