<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers\Dashboard;

use App\Modules\Audit\AuditLogger;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Invitation;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\TwoFactor\TwoFactor;
use App\Support\Api\ApiException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Dashboard sign-in for lawyers and admins (PROJECT1_PLAN.md §4.2, S5.7): Sanctum SPA cookie session, email +
 * password, then a TOTP code. Two-factor is mandatory: a user without it is signed in only far enough to enrol, and
 * `two_factor` middleware keeps every lawyer and admin route closed until a code has been checked in this session.
 */
final class DashboardAuthController
{
    public const PENDING = 'login.pending_id';

    public const PENDING_AT = 'login.pending_at';

    public const PASSED = 'two_factor.passed_at';

    private const PENDING_TTL = 300;

    public function __construct(private readonly TwoFactor $twoFactor, private readonly AuditLogger $audit) {}

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255'], 'password' => ['required', 'string', 'max:255']]);
        $user = User::query()->where('email', mb_strtolower($data['email']))->first();
        $allowed = $user !== null && $user->status === UserStatus::Active && $user->hasAnyRole([Role::Lawyer->value, Role::Admin->value]);
        // Hash::check runs even for unknown emails, so timing doesn't tell which emails exist.
        $passwordOk = Hash::check($data['password'], $user->password ?? '$2y$12$'.str_repeat('a', 53));
        if (! $allowed || ! $passwordOk) {
            if ($user !== null) {
                $this->audit->record('dashboard.login_failed', $user, $user);
            }
            throw new ApiException(422, 'invalid_credentials', 'البريد الإلكتروني أو كلمة المرور غير صحيحة.');
        }

        $request->session()->regenerate();
        if ($user->two_factor_confirmed_at === null) {
            Auth::guard('web')->login($user);

            return response()->json(['data' => ['two_factor_required' => false, 'enrolment_required' => true]]);
        }
        $request->session()->put([self::PENDING => $user->id, self::PENDING_AT => now()->getTimestamp()]);

        return response()->json(['data' => ['two_factor_required' => true, 'enrolment_required' => false]]);
    }

    public function challenge(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['nullable', 'string', 'max:10'], 'recovery_code' => ['nullable', 'string', 'max:30']]);
        $id = $request->session()->get(self::PENDING);
        $at = (int) $request->session()->get(self::PENDING_AT, 0);
        $user = is_string($id) && now()->getTimestamp() - $at <= self::PENDING_TTL ? User::query()->find($id) : null;
        if ($user === null || $user->status !== UserStatus::Active) {
            $request->session()->forget([self::PENDING, self::PENDING_AT]);
            throw new ApiException(401, 'login_expired', 'انتهت مهلة تسجيل الدخول. ابدأ من جديد.');
        }

        $this->twoFactor->check($user, $data['code'] ?? null, $data['recovery_code'] ?? null);

        $request->session()->forget([self::PENDING, self::PENDING_AT]);
        $request->session()->regenerate();
        Auth::guard('web')->login($user);
        $request->session()->put(self::PASSED, now()->getTimestamp());
        $user->forceFill(['last_login_at' => now()])->save();
        $this->audit->record('dashboard.login', $user, $user);

        return response()->json(['data' => $this->present($user, $request)]);
    }

    /** Enrolment step 1 (signed in, not yet enrolled): secret + otpauth URI for the QR, and the recovery codes once. */
    public function enable(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->twoFactor->begin($this->current($request))]);
    }

    /** Enrolment step 2: a code from the app turns two-factor on and counts as passed for this session. */
    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:10']]);
        $user = $this->current($request);
        $this->twoFactor->confirm($user, $data['code']);
        $request->session()->regenerate();
        $request->session()->put(self::PASSED, now()->getTimestamp());
        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json(['data' => $this->present($user, $request)]);
    }

    private function current(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->present($this->current($request), $request)]);
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    /** «تعيين كلمة المرور»: shows who the link is for. */
    public function invitation(string $token): JsonResponse
    {
        $invitation = $this->openInvitation($token);

        return response()->json(['data' => ['name' => $invitation->name, 'email' => $invitation->email, 'role' => $invitation->role->value]]);
    }

    /** Sets the password from an invite link, signs the user in for enrolment and starts it (QR + recovery codes). */
    public function acceptInvitation(Request $request, string $token): JsonResponse
    {
        $data = $request->validate(['password' => ['required', 'string', 'max:255', Password::min(10)->letters()->numbers()]]); // the rule shown on «تعيين كلمة المرور»

        $user = DB::transaction(function () use ($token, $data): User {
            $invitation = $this->openInvitation($token, lock: true);
            $email = mb_strtolower((string) $invitation->email);
            if (User::query()->withTrashed()->where('email', $email)->exists()) {
                throw new ApiException(409, 'account_exists', 'يوجد حساب بهذا البريد. سجّل الدخول أو تواصل مع الإدارة.');
            }
            $user = User::query()->create(['name' => $invitation->name, 'email' => $email, 'password' => $data['password'], 'status' => UserStatus::Active]);
            $user->assignRole($invitation->role->value);
            $invitation->forceFill(['accepted_at' => now()])->save();
            $this->audit->record('invitation.accepted', $user, $invitation, ['role' => $invitation->role->value]);

            return $user;
        });

        $request->session()->regenerate();
        Auth::guard('web')->login($user);

        return response()->json(['data' => $this->twoFactor->begin($user)], 201);
    }

    private function openInvitation(string $token, bool $lock = false): Invitation
    {
        $query = Invitation::query()->open()->where('token_hash', hash('sha256', $token))
            ->whereIn('role', [Role::Lawyer->value, Role::Admin->value])->whereNotNull('email');
        $invitation = ($lock ? $query->lockForUpdate() : $query)->first();
        if ($invitation === null) {
            throw new ApiException(404, 'invitation_invalid', 'رابط الدعوة غير صالح أو منتهي الصلاحية.');
        }

        return $invitation;
    }

    /** @return array<string, mixed> */
    private function present(User $user, Request $request): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->hasRole(Role::Admin->value) ? 'admin' : 'lawyer',
            'two_factor' => [
                'enabled' => $user->two_factor_confirmed_at !== null,
                'passed' => $request->session()->has(self::PASSED),
            ],
        ];
    }
}
