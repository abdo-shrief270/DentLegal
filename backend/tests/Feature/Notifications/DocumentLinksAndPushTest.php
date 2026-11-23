<?php

namespace Tests\Feature\Notifications;

use App\Modules\Cases\Models\DentalCase;
use App\Modules\Documents\Actions\UploadDocument;
use App\Modules\Documents\Enums\DocumentKind;
use App\Modules\Documents\Models\Document;
use App\Modules\Identity\Http\Controllers\Dashboard\DashboardAuthController;
use App\Modules\Identity\Models\User;
use App\Modules\Notifications\Jobs\SendDraftApprovedPush;
use App\Modules\Notifications\Push\FcmPushGateway;
use App\Modules\Notifications\Push\PushGateway;
use App\Modules\Notifications\Push\PushResult;
use App\Modules\Pipeline\Models\Draft;
use App\Modules\Review\Events\DraftApproved;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Signed links to original documents (§4.2) and the «رد معتمد جاهز» push (S5.5). Synthetic data only. */
class DocumentLinksAndPushTest extends TestCase
{
    private const PDF = "%PDF-1.7\n% synthetic original\n%%EOF";

    private User $dentist;

    private DentalCase $case;

    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
        $this->dentist = $this->actingAsDentist();
        $this->case = DentalCase::factory()->for($this->dentist, 'dentist')->create();
        $this->document = app(UploadDocument::class)($this->dentist, $this->case, UploadedFile::fake()->createWithContent('letter.pdf', self::PDF), DocumentKind::ComplaintLetter);
    }

    public function test_dentist_link_opens_the_decrypted_file_for_five_minutes(): void
    {
        $url = $this->getJson("/api/v1/documents/{$this->document->id}/view-url")->assertOk()->json('data.url');

        $this->app['auth']->forgetGuards();
        $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('Cache-Control', 'max-age=0, no-store, private');
        $this->assertSame(self::PDF, $response->getContent());
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.viewed', 'subject_id' => $this->document->id, 'actor_id' => $this->dentist->id]);

        $this->get(str_replace('signature=', 'signature=0', $url))->assertForbidden();
        $this->travel(6)->minutes();
        $this->get($url)->assertForbidden();
    }

    public function test_only_the_owner_or_the_claiming_lawyer_gets_a_link(): void
    {
        $this->actingAsDentist();
        $this->getJson("/api/v1/documents/{$this->document->id}/view-url")->assertNotFound();

        $lawyer = User::factory()->lawyer()->create(['two_factor_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 'two_factor_confirmed_at' => now()]);
        $this->app['auth']->forgetGuards();
        $this->withHeaders(['Origin' => 'http://localhost', 'Referer' => 'http://localhost/'])->actingAs($lawyer, 'web')->withSession([DashboardAuthController::PASSED => time()]);
        $draft = Draft::query()->create(['case_id' => $this->case->id, 'status' => 'in_review']);
        $this->getJson("/api/v1/lawyer/documents/{$this->document->id}/view-url")->assertNotFound();

        $draft->update(['reviewer_id' => $lawyer->id, 'claimed_at' => now()]);
        $this->getJson("/api/v1/lawyer/documents/{$this->document->id}/view-url")->assertOk()->assertJsonStructure(['data' => ['url', 'expires_at']]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.view_url_issued', 'actor_id' => $lawyer->id]);
    }

    public function test_approval_queues_the_push_and_dead_tokens_are_cleared(): void
    {
        Queue::fake();
        event(new DraftApproved('d', $this->case->id, $this->dentist->id));
        Queue::assertPushed(SendDraftApprovedPush::class, fn (SendDraftApprovedPush $job) => $job->caseId === $this->case->id);

        $live = $this->dentist->devices()->create(['platform' => 'ios', 'fcm_token' => 'token-live']);
        $dead = $this->dentist->devices()->create(['platform' => 'android', 'fcm_token' => 'token-dead']);
        $sent = [];
        $this->app->instance(PushGateway::class, new class($sent) implements PushGateway
        {
            /** @param list<array<string, mixed>> $sent */
            public function __construct(private array &$sent) {}

            public function send(string $deviceToken, string $title, string $body, array $data): PushResult
            {
                $this->sent[] = compact('deviceToken', 'title', 'body', 'data');

                return $deviceToken === 'token-dead' ? PushResult::InvalidToken : PushResult::Sent;
            }
        });
        app()->call([new SendDraftApprovedPush($this->case->id), 'handle']);

        $this->assertCount(2, $sent);
        $this->assertSame(['type' => 'draft_approved', 'case_id' => $this->case->id], $sent[0]['data']);
        $this->assertStringContainsString($this->case->code, $sent[0]['body']);
        $this->assertNotNull($live->refresh()->fcm_token);
        $this->assertNull($dead->refresh()->fcm_token);
    }

    public function test_fcm_v1_gateway_signs_a_jwt_and_reads_dead_tokens(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem);
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'ya29.test', 'expires_in' => 3600]),
            'fcm.googleapis.com/*' => Http::sequence()->push(['name' => 'projects/p/messages/1'])->push(['error' => ['status' => 'NOT_FOUND', 'message' => 'Requested entity was not found.']], 404),
        ]);
        $gateway = new FcmPushGateway(['project_id' => 'dentlegal-test', 'client_email' => 'push@dentlegal-test.iam.gserviceaccount.com', 'private_key' => $pem]);

        $this->assertSame(PushResult::Sent, $gateway->send('t1', 'عنوان', 'نص', ['type' => 'draft_approved']));
        $this->assertSame(PushResult::InvalidToken, $gateway->send('t2', 'عنوان', 'نص', []));

        Http::assertSent(function (Request $r) use ($key): bool {
            if (! str_contains($r->url(), 'oauth2')) {
                return false;
            }
            [$h, $p, $sig] = explode('.', (string) $r['assertion']);
            $claims = json_decode(base64_decode(strtr($p, '-_', '+/')), true);
            $valid = openssl_verify("{$h}.{$p}", base64_decode(strtr($sig, '-_', '+/')), openssl_pkey_get_details($key)['key'], OPENSSL_ALGO_SHA256) === 1;

            return $valid && $claims['scope'] === 'https://www.googleapis.com/auth/firebase.messaging';
        });
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'projects/dentlegal-test/messages:send') && $r->hasHeader('Authorization', 'Bearer ya29.test') && $r['message']['token'] === 't1');
    }
}
