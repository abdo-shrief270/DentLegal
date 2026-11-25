<?php

namespace Database\Seeders;

use App\Modules\Cases\Enums\CaseStatus;
use App\Modules\Cases\Models\DentalCase;
use App\Modules\Documents\Models\Document;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\LawyerProfile;
use App\Modules\Identity\Models\User;
use App\Modules\Knowledge\Models\KnowledgeSource;
use App\Modules\Knowledge\Models\KnowledgeSourceVersion;
use App\Modules\Pipeline\Models\Analysis;
use App\Modules\Pipeline\Models\Draft;
use App\Modules\Pipeline\Support\AiRunRecorder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Accounts and one case for the dashboard smoke test (web/dashboard/e2e, S9.4) on local or staging, never production.
 *
 * Creates (or resets) a lawyer and an admin with the credentials from SMOKE_* environment variables, and adds one
 * synthetic case waiting for review with an AI draft. Run it before each smoke-test run: the test claims and approves
 * the case. All data is synthetic.
 *
 *   SMOKE_LAWYER_EMAIL / SMOKE_LAWYER_PASSWORD / SMOKE_LAWYER_TOTP_SECRET (base32), and the same with SMOKE_ADMIN_*.
 */
class SmokeTestSeeder extends Seeder
{
    private const ARTICLE = 'يجب على الممارس الصحي أن يحافظ على الأسرار التي علم بها عن طريق مهنته، ولا يجوز له إفشاؤها إلا لدفع اتهام وجهه إليه المريض.';

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \RuntimeException('SmokeTestSeeder must not run in production.');
        }
        $this->call(RoleSeeder::class);

        $lawyer = $this->account('LAWYER', Role::Lawyer, 'محامية الاختبار الآلي');
        LawyerProfile::query()->updateOrCreate(['user_id' => $lawyer->id], ['license_no' => 'TEST-0001', 'firm_name' => 'حساب اختبار']);
        $this->account('ADMIN', Role::Admin, 'إدارة الاختبار الآلي');

        $this->reviewCase();
        $this->command?->info('Smoke-test accounts ready and one case waiting for review.');
    }

    private function account(string $prefix, Role $role, string $name): User
    {
        $env = function (string $key) use ($prefix): string {
            $value = env("SMOKE_{$prefix}_{$key}");
            if (! is_string($value) || $value === '') {
                throw new \RuntimeException("SMOKE_{$prefix}_{$key} is not set.");
            }

            return $value;
        };
        $user = User::query()->firstOrNew(['email' => $env('EMAIL')]);
        $user->forceFill([
            'name' => $name, 'phone_e164' => null, 'password' => $env('PASSWORD'), 'status' => 'active',
            'two_factor_secret' => strtoupper($env('TOTP_SECRET')), 'two_factor_confirmed_at' => now(),
        ])->save();
        $user->syncRoles([$role->value]);

        return $user;
    }

    private function reviewCase(): void
    {
        $source = KnowledgeSource::query()->firstOrCreate(['code' => 'SMOKE'], ['title_ar' => 'مصدر اختبار آلي (غير منشور)', 'type' => 'law']);
        $version = KnowledgeSourceVersion::query()->firstOrCreate(['source_id' => $source->id, 'version_label' => 'test'], ['status' => 'review']);
        $chunkId = DB::table('knowledge_chunks')->where('chunk_key', 'SMOKE:test:A21')->value('id')
            ?? DB::selectOne(
                "INSERT INTO knowledge_chunks (version_id, source_id, status, chunk_key, ordinal, chunk_type, article_no, display_text, search_text, token_count)
                 VALUES (?, ?, 'review', 'SMOKE:test:A21', 1, 'article', 21, ?, ?, 40) RETURNING id",
                [$version->id, $source->id, self::ARTICLE, self::ARTICLE],
            )->id;

        $case = DentalCase::factory()->status(CaseStatus::LawyerReview)
            ->create(['title' => 'حالة اختبار آلي: ادعاء إفشاء معلومات', 'patient_code' => 'P-TEST', 'submitted_for_review_at' => now()]);
        $doc = (new Document)->forceFill([
            'case_id' => $case->id, 'kind' => 'complaint_letter', 'original_name' => 'test-letter.pdf', 'mime' => 'application/pdf', 'size_bytes' => 100,
            'storage_key' => 'smoke-test/missing', 'wrapped_key' => 'x', 'sha256' => str_repeat('0', 64), 'page_count' => 1, 'ocr_status' => 'done', 'ocr_confidence' => 0.95,
        ]);
        $doc->save();
        $doc->pages()->create(['page_no' => 1, 'text_masked' => 'يدّعي [PATIENT] أن الطبيب أفشى تفاصيل علاجه.', 'confidence' => 0.95, 'method' => 'ocr', 'masked_entity_counts' => []]);

        $run = app(AiRunRecorder::class)->record(['run_type' => 'case_draft', 'llm' => [['model_requested' => 'smoke-test', 'cost_usd' => 0]]],
            [['ref' => 'S1', 'chunk_id' => (int) $chunkId, 'rerank_score' => 0.9]], 'succeeded');
        Analysis::query()->create(['case_id' => $case->id, 'position_strength' => 70, 'risk_level' => 'low', 'result_json' => ['summary' => 'حالة اختبار.'],
            'source_chunk_ids' => [(int) $chunkId], 'sources_sufficient' => true, 'needs_attention' => false, 'is_current' => true]);

        $para = fn (string $text, bool $legal, array $citations) => ['text' => $text, 'legal_claim' => $legal, 'citations' => $citations];
        $draft = Draft::query()->create(['case_id' => $case->id, 'status' => 'in_review']);
        $v1 = $draft->versions()->create(['version_no' => 1, 'author_type' => 'ai', 'ai_run_id' => $run?->id, 'content_json' => [
            'title' => 'إفادة الممارس الصحي (اختبار)', 'addressee' => 'سعادة مدير الشؤون الصحية', 'salutation' => 'السلام عليكم ورحمة الله وبركاته',
            'closing' => 'وتقبلوا وافر التحية', 'attachments' => [],
            'intro' => $para('أتقدم أنا {{DENTIST_NAME}} بإفادتي بشأن شكوى [PATIENT].', false, []),
            'sections' => [
                ['kind' => 'facts', 'heading' => 'أولًا: الوقائع', 'paragraphs' => [$para('ادعى المريض إفشاء تفاصيل علاجه.', false, [['ref' => 'D1', 'quote' => 'أفشى تفاصيل علاجه']])]],
                ['kind' => 'defenses', 'heading' => 'ثانيًا: الدفوع', 'paragraphs' => [$para('يجوز الإفصاح بالقدر اللازم لدفع الاتهام.', true, [['ref' => 'S1', 'quote' => 'لا يجوز له إفشاؤها إلا لدفع اتهام']])]],
                ['kind' => 'requests', 'heading' => 'ثالثًا: الطلبات', 'paragraphs' => [$para('ألتمس حفظ الشكوى.', false, [])]],
            ],
        ]]);
        $draft->update(['current_version_id' => $v1->id]);
    }
}
