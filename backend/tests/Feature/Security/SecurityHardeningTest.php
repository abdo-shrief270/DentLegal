<?php

namespace Tests\Feature\Security;

use App\Modules\Identity\Http\Controllers\Dashboard\DashboardAuthController;
use App\Modules\Identity\Models\User;
use Illuminate\Routing\Route as RouteDef;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** PROJECT1_PLAN.md §9.3, S9.3: headers, CORS, rate-limit coverage, closed debug surfaces, production config check. */
class SecurityHardeningTest extends TestCase
{
    public function test_api_responses_carry_the_security_headers(): void
    {
        $this->actingAsDentist();
        $response = $this->getJson('/api/v1/cases')->assertOk();

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'no-referrer');
        $response->assertHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'none'");
        $response->assertHeaderMissing('X-Powered-By');
        $response->assertHeaderMissing('Strict-Transport-Security'); // plain http in tests

        $this->get('https://localhost/up')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_cors_allows_only_the_dashboard_origin_with_credentials(): void
    {
        $preflight = fn (string $origin) => $this->call('OPTIONS', '/api/v1/lawyer/reviews', server: [
            'HTTP_ORIGIN' => $origin, 'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ]);

        $ok = $preflight((string) config('dentlegal.invitations.dashboard_url'));
        $ok->assertHeader('Access-Control-Allow-Origin', config('dentlegal.invitations.dashboard_url'));
        $ok->assertHeader('Access-Control-Allow-Credentials', 'true');

        // A single allowed origin is always echoed as itself, never as the caller's: browsers then refuse the response.
        $this->assertNotSame('https://evil.example', $preflight('https://evil.example')->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_every_api_and_dashboard_route_is_rate_limited(): void
    {
        // Framework and tooling routes outside the product API.
        $exempt = ['up', '/', 'sanctum/csrf-cookie', 'docs/api', 'docs/api.json', '_scramble/dev-tools/devtools.js'];
        $missing = collect(Route::getRoutes()->getRoutes())
            ->reject(fn (RouteDef $r) => in_array($r->uri(), $exempt, true) || str_starts_with($r->uri(), 'horizon'))
            ->reject(fn (RouteDef $r) => collect(Route::gatherRouteMiddleware($r))->contains(fn ($m) => is_string($m) && str_contains($m, 'ThrottleRequests')))
            ->map(fn (RouteDef $r) => implode('|', $r->methods()).' '.$r->uri())
            ->values()->all();

        $this->assertSame([], $missing, 'routes without a throttle');
    }

    public function test_debug_and_file_surfaces_are_closed(): void
    {
        $this->assertFalse(collect(Route::getRoutes()->getRoutes())->contains(fn (RouteDef $r) => str_starts_with($r->uri(), 'storage/')));

        // API docs only in the local environment (Scramble's default with no viewApiDocs gate).
        $this->app['env'] = 'production';
        $this->get('/docs/api')->assertForbidden();
    }

    public function test_horizon_is_for_admins_with_two_factor_in_this_session(): void
    {
        $admin = User::factory()->create(['phone_e164' => null, 'email' => 'admin@dentlegal.test', 'password' => 'password-123', 'two_factor_confirmed_at' => now()]);
        $admin->assignRole('admin');
        $lawyer = User::factory()->lawyer()->create(['two_factor_confirmed_at' => now()]);

        $this->get('/horizon')->assertForbidden();
        $this->actingAs($admin, 'web')->get('/horizon')->assertForbidden(); // no code checked in this session
        $this->actingAs($admin, 'web')->withSession([DashboardAuthController::PASSED => time()])->get('/horizon')->assertOk();

        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->actingAs($lawyer, 'web')->withSession([DashboardAuthController::PASSED => time()])->get('/horizon')->assertForbidden();
    }

    public function test_security_check_passes_a_production_configuration_and_fails_debug(): void
    {
        $this->app['env'] = 'production';
        config([
            'app.debug' => false, 'app.url' => 'https://api.dentlegal.example', 'app.key' => 'base64:'.base64_encode(random_bytes(32)),
            'dentlegal.documents.master_key' => base64_encode(random_bytes(32)),
            'dentlegal.ai_service.token' => str_repeat('t', 40), 'dentlegal.ai_service.url' => 'http://ai:8000',
            'session.secure' => true, 'session.http_only' => true, 'session.lifetime' => 120,
            'dentlegal.invitations.dashboard_url' => 'https://portal.dentlegal.example',
            'sanctum.stateful' => ['portal.dentlegal.example'], 'cors.allowed_origins' => ['https://portal.dentlegal.example'],
            'logging.channels.single.level' => 'info', 'logging.channels.stderr.level' => 'info',
        ]);

        $this->artisan('security:check')->expectsOutputToContain('safe for production')->assertSuccessful();

        config(['app.debug' => true]);
        $this->artisan('security:check')->expectsOutputToContain('APP_DEBUG is on')->assertFailed();
    }
}
