<?php

namespace Tests\Feature\Admin;

use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/** `dentlegal:invite-admin`: the first admin on a new server gets a one-time link that the dashboard accepts. */
class InviteAdminCommandTest extends TestCase
{
    public function test_the_printed_link_creates_an_admin_with_two_factor_to_enrol(): void
    {
        $this->assertSame(0, Artisan::call('dentlegal:invite-admin', ['email' => 'First.Admin@dentlegal.test', 'name' => 'مدير المنصة']));
        preg_match('#/invite/([A-Za-z0-9]{48})#', Artisan::output(), $m);
        $this->assertNotEmpty($m, 'the command prints the invitation link');
        $this->assertDatabaseHas('audit_logs', ['action' => 'invitation.created']);

        $this->withHeaders(['Origin' => 'http://localhost', 'Referer' => 'http://localhost/'])
            ->postJson("/invitations/{$m[1]}/accept", ['password' => 'a-long-password-123'])
            ->assertSuccessful();
        $this->assertTrue(User::query()->where('email', 'first.admin@dentlegal.test')->firstOrFail()->hasRole('admin'));

        $this->artisan('dentlegal:invite-admin', ['email' => 'first.admin@dentlegal.test', 'name' => 'x'])->assertFailed();
        $this->artisan('dentlegal:invite-admin', ['email' => 'not-an-email', 'name' => 'x'])->assertFailed();
    }
}
