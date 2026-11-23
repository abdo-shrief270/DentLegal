<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Admin\Actions\InviteUser;
use App\Modules\Admin\Actions\ResendInvitation;
use App\Modules\Admin\Actions\SetUserActive;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\Invitation;
use App\Modules\Identity\Models\User;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * «الأطباء» and «المحامون» (PROJECT1_PLAN.md S7.1). A row is either an account (id = user id) or an invitation not yet
 * accepted (status `invited`, id = invitation id). Phones are shown masked.
 */
final class AdminUserController
{
    public function dentists(): JsonResponse
    {
        $users = User::query()->role(Role::Dentist->value)->with('dentistProfile')
            ->withCount(['cases', 'consultations'])->orderByDesc('created_at')->limit(500)->get();
        $rows = $users->map(fn (User $u): array => self::dentistRow($u))->all();
        $invites = Invitation::query()->where('role', Role::Dentist->value)->whereNull('accepted_at')->whereNull('revoked_at')->orderByDesc('created_at')->get();

        return response()->json(['data' => [...$invites->map(fn (Invitation $i): array => self::dentistInviteRow($i))->all(), ...$rows]]);
    }

    public function inviteDentist(Request $request, InviteUser $invite): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'specialty' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:60'],
        ]);
        $phone = Phone::normalizeSaudiMobile($data['phone']) ?? throw ValidationException::withMessages(['phone' => 'رقم الجوال غير صحيح.']);
        $invitation = $invite->dentist($this->admin($request), ['name' => $data['name'], 'phone_e164' => $phone, 'specialty' => $data['specialty'] ?? null, 'city' => $data['city'] ?? null]);

        return response()->json(['data' => self::dentistInviteRow($invitation)], 201);
    }

    public function lawyers(): JsonResponse
    {
        $users = User::query()->role(Role::Lawyer->value)->with('lawyerProfile')->orderByDesc('created_at')->limit(200)->get();
        $stats = DB::table('drafts')->whereIn('reviewer_id', $users->pluck('id'))->groupBy('reviewer_id')->selectRaw(
            "reviewer_id,
             count(*) filter (where status = 'in_review') as in_review,
             count(*) filter (where status = 'approved' and approved_at >= ?) as approved_this_month,
             avg(extract(epoch from (approved_at - claimed_at)) / 3600) filter (where status = 'approved') as avg_hours",
            [now()->startOfMonth()],
        )->get()->keyBy('reviewer_id');
        $invites = Invitation::query()->where('role', Role::Lawyer->value)->whereNull('accepted_at')->whereNull('revoked_at')->orderByDesc('created_at')->get();

        return response()->json(['data' => [
            ...$invites->map(fn (Invitation $i): array => self::lawyerInviteRow($i))->all(),
            ...$users->map(fn (User $u): array => self::lawyerRow($u, $stats->get($u->id)))->all(),
        ]]);
    }

    public function inviteLawyer(Request $request, InviteUser $invite): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'license_no' => ['required', 'string', 'max:40'],
            'firm' => ['nullable', 'string', 'max:160'],
        ]);
        $invitation = $invite->lawyer($this->admin($request), ['name' => $data['name'], 'email' => $data['email'], 'license_no' => $data['license_no'], 'firm_name' => $data['firm'] ?? null]);

        return response()->json(['data' => self::lawyerInviteRow($invitation)], 201);
    }

    /** {id} is the invitation id of an `invited` row. */
    public function resendInvite(Request $request, string $id, ResendInvitation $resend): JsonResponse
    {
        $invitation = Invitation::query()->whereKey($id)->whereIn('role', [Role::Dentist->value, Role::Lawyer->value])->firstOrFail();
        $resend($this->admin($request), $invitation);

        return response()->json(['data' => ['id' => $invitation->id, 'expires_at' => $invitation->expires_at->toIso8601ZuluString()]]);
    }

    public function activate(Request $request, User $user, SetUserActive $set): JsonResponse
    {
        $set($this->admin($request), $user, true);

        return response()->json(['data' => ['id' => $user->id, 'status' => $user->status->value]]);
    }

    public function deactivate(Request $request, User $user, SetUserActive $set): JsonResponse
    {
        $set($this->admin($request), $user, false);

        return response()->json(['data' => ['id' => $user->id, 'status' => $user->status->value]]);
    }

    /** @return array<string, mixed> */
    private static function dentistRow(User $u): array
    {
        return [
            'id' => $u->id, 'name' => $u->name, 'phone' => $u->phone_e164 ? Phone::mask($u->phone_e164) : null,
            'specialty' => $u->dentistProfile?->specialty, 'city' => $u->dentistProfile?->city, 'status' => $u->status->value,
            'joined_at' => $u->created_at->toIso8601ZuluString(), 'last_active_at' => $u->last_login_at?->toIso8601ZuluString(),
            'cases' => (int) $u->getAttribute('cases_count'), 'consultations' => (int) $u->getAttribute('consultations_count'),
        ];
    }

    /** @return array<string, mixed> */
    private static function dentistInviteRow(Invitation $i): array
    {
        return [
            'id' => $i->id, 'name' => $i->name, 'phone' => $i->phone_e164 ? Phone::mask($i->phone_e164) : null,
            'specialty' => $i->meta['specialty'] ?? null, 'city' => $i->meta['city'] ?? null, 'status' => 'invited',
            'invite_expired' => $i->expires_at->isPast(), 'joined_at' => $i->created_at->toIso8601ZuluString(), 'last_active_at' => null,
            'cases' => 0, 'consultations' => 0,
        ];
    }

    /** @return array<string, mixed> */
    private static function lawyerRow(User $u, ?object $stats): array
    {
        return [
            'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'firm' => $u->lawyerProfile?->firm_name, 'license_no' => $u->lawyerProfile?->license_no,
            'status' => $u->status->value, 'in_review' => (int) ($stats->in_review ?? 0), 'approved_this_month' => (int) ($stats->approved_this_month ?? 0),
            'avg_review_hours' => isset($stats->avg_hours) ? round((float) $stats->avg_hours, 1) : null,
            'two_factor_enabled' => $u->two_factor_confirmed_at !== null,
        ];
    }

    /** @return array<string, mixed> */
    private static function lawyerInviteRow(Invitation $i): array
    {
        return [
            'id' => $i->id, 'name' => $i->name, 'email' => $i->email, 'firm' => $i->meta['firm_name'] ?? null, 'license_no' => $i->meta['license_no'] ?? null,
            'status' => 'invited', 'invite_expired' => $i->expires_at->isPast(), 'in_review' => 0, 'approved_this_month' => 0, 'avg_review_hours' => null,
            'two_factor_enabled' => false,
        ];
    }

    private function admin(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
