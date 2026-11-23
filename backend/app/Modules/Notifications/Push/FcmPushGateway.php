<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Push;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Firebase Cloud Messaging HTTP v1 with the client's service account (PROJECT1_PLAN.md §8.8). The OAuth access token
 * comes from a JWT signed with the service-account key (RS256) and is cached for 50 minutes. APNs is reached through
 * FCM. 404 / UNREGISTERED and INVALID_ARGUMENT on the token mean the token is dead.
 */
final class FcmPushGateway implements PushGateway
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    /** @param array{project_id: string, client_email: string, private_key: string, token_uri?: string} $account */
    public function __construct(private readonly array $account) {}

    public static function fromFile(string $path): self
    {
        $json = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        if (! is_array($json) || ! isset($json['project_id'], $json['client_email'], $json['private_key'])) {
            throw new RuntimeException('FCM service account file is missing or invalid');
        }

        return new self($json);
    }

    public function send(string $deviceToken, string $title, string $body, array $data): PushResult
    {
        $response = Http::withToken($this->accessToken())->timeout(10)->retry(2, 500, fn (\Throwable $e): bool => $e instanceof ConnectionException || ($e instanceof RequestException && $e->response->serverError()), throw: false)
            ->post("https://fcm.googleapis.com/v1/projects/{$this->account['project_id']}/messages:send", ['message' => [
                'token' => $deviceToken,
                'notification' => ['title' => $title, 'body' => $body],
                'data' => $data,
                'android' => ['priority' => 'high'],
                'apns' => ['payload' => ['aps' => ['sound' => 'default']]],
            ]]);
        if ($response->successful()) {
            return PushResult::Sent;
        }
        $status = (string) $response->json('error.status');
        if ($response->status() === 404 || in_array($status, ['NOT_FOUND', 'UNREGISTERED'], true)
            || ($status === 'INVALID_ARGUMENT' && str_contains((string) $response->json('error.message'), 'token'))) {
            return PushResult::InvalidToken;
        }
        Log::warning('fcm_send_failed', ['status' => $response->status(), 'error' => $status]);

        return PushResult::Failed;
    }

    private function accessToken(): string
    {
        return Cache::remember('fcm:access_token', 3000, function (): string {
            $now = time();
            $tokenUri = $this->account['token_uri'] ?? 'https://oauth2.googleapis.com/token';
            $segments = [
                self::b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR)),
                self::b64(json_encode(['iss' => $this->account['client_email'], 'scope' => self::SCOPE, 'aud' => $tokenUri, 'iat' => $now, 'exp' => $now + 3600], JSON_THROW_ON_ERROR)),
            ];
            if (! openssl_sign(implode('.', $segments), $signature, $this->account['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('could not sign the FCM token request');
            }
            $segments[] = self::b64($signature);
            $response = Http::asForm()->timeout(10)->post($tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => implode('.', $segments),
            ])->throw();

            return (string) $response->json('access_token');
        });
    }

    private static function b64(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
