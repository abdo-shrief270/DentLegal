<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Actions\RequestOtp;
use App\Modules\Identity\Actions\VerifyOtp;
use App\Modules\Identity\Http\Requests\RequestOtpRequest;
use App\Modules\Identity\Http\Requests\VerifyOtpRequest;
use App\Modules\Identity\Http\Resources\MeResource;
use Illuminate\Http\JsonResponse;

final class OtpController
{
    /** Send a 6-digit code to an invited or active dentist. */
    public function request(RequestOtpRequest $request, RequestOtp $action): JsonResponse
    {
        return response()->json(['data' => $action($request->phone(), $request->ip())], 202);
    }

    /** Verify the code and issue a 30-day token for this device. */
    public function verify(VerifyOtpRequest $request, VerifyOtp $action): JsonResponse
    {
        /** @var array{platform: string, name?: string|null, app_version?: string|null} $device */
        $device = $request->validated('device');
        $result = $action($request->phone(), $request->string('code')->toString(), $device);
        $result['user']->load('dentistProfile');

        return response()->json(['data' => [
            'token' => $result['token'],
            'needs_profile' => $result['user']->needsProfile(),
            'user' => new MeResource($result['user']),
        ]]);
    }
}
