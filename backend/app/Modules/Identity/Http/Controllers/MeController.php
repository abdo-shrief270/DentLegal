<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Audit\AuditLogger;
use App\Modules\Identity\Actions\CompleteDentistProfile;
use App\Modules\Identity\Actions\DeleteAccount;
use App\Modules\Identity\Actions\RecordConsents;
use App\Modules\Identity\Http\Requests\RecordConsentsRequest;
use App\Modules\Identity\Http\Requests\UpdateDeviceRequest;
use App\Modules\Identity\Http\Requests\UpdateProfileRequest;
use App\Modules\Identity\Http\Resources\MeResource;
use App\Modules\Identity\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;

final class MeController
{
    public function show(Request $request): MeResource
    {
        return new MeResource($this->user($request)->load('dentistProfile'));
    }

    public function updateProfile(UpdateProfileRequest $request, CompleteDentistProfile $action): MeResource
    {
        /** @var array{name: string, specialty: string, city: string, facility_name: string, scfhs_license_no?: string|null} $data */
        $data = $request->validated();

        return new MeResource($action($this->user($request), $data));
    }

    public function consents(RecordConsentsRequest $request, RecordConsents $action): MeResource
    {
        /** @var list<array{type: string, version: string}> $consents */
        $consents = $request->validated('consents');
        $action($this->user($request), $consents, $request->ip());

        return new MeResource($this->user($request)->load('dentistProfile'));
    }

    /** Update the FCM token and app version of the device that holds this token. */
    public function updateDevice(UpdateDeviceRequest $request): JsonResponse
    {
        $user = $this->user($request);
        $device = $user->devices()->where('token_id', $this->tokenId($request))->firstOrFail();
        $device->fill($request->validated())->forceFill(['last_seen_at' => now()])->save();

        return response()->json(['data' => ['id' => $device->id, 'platform' => $device->platform->value]]);
    }

    public function logout(Request $request, AuditLogger $audit): Response
    {
        $user = $this->user($request);
        $tokenId = $this->tokenId($request);
        $user->devices()->where('token_id', $tokenId)->delete();
        $user->tokens()->whereKey($tokenId)->delete();
        $audit->record('auth.logout', $user, $user);

        return response()->noContent();
    }

    public function destroy(Request $request, DeleteAccount $action): Response
    {
        $action($this->user($request));

        return response()->noContent();
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    private function tokenId(Request $request): int|string|null
    {
        $token = $this->user($request)->currentAccessToken();

        // Sanctum::actingAs and cookie sessions give a TransientToken, which has no id.
        /** @phpstan-ignore instanceof.alwaysTrue */
        return $token instanceof PersonalAccessToken ? $token->getKey() : null;
    }
}
