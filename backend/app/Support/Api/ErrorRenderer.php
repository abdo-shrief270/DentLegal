<?php

declare(strict_types=1);

namespace App\Support\Api;

use App\Support\ErrorTracking\ErrorReporter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/** Renders every API failure in one envelope so the app and the dashboard handle errors one way. */
final class ErrorRenderer
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->dontReport([ApiException::class]);
        // Unexpected errors also go to error tracking (Laravel has already skipped validation, auth, 404 and the like).
        $exceptions->report(function (Throwable $e): void {
            app(ErrorReporter::class)->capture($e);
        });

        $exceptions->render(function (Throwable $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return self::toResponse($e);
        });
    }

    public static function toResponse(Throwable $e): JsonResponse
    {
        return match (true) {
            $e instanceof ApiException => self::json($e->status, $e->errorCode, $e->getMessage(), $e->details, $e->headers),
            $e instanceof ValidationException => self::json(422, 'validation_failed', 'تحقق من البيانات المدخلة.', ['fields' => $e->errors()])
                ->setData(['error' => ['code' => 'validation_failed', 'message' => 'تحقق من البيانات المدخلة.', 'details' => ['fields' => $e->errors()]], 'errors' => $e->errors()]),
            $e instanceof AuthenticationException => self::json(401, 'unauthenticated', 'انتهت الجلسة. سجّل الدخول مرة أخرى.'),
            // Resources of other users are reported as missing so their ids are not revealed (§9.4).
            $e instanceof ModelNotFoundException, $e instanceof NotFoundHttpException => self::json(404, 'not_found', 'العنصر المطلوب غير موجود.'),
            $e instanceof AuthorizationException, $e instanceof AccessDeniedHttpException => self::json(403, 'forbidden', 'ليست لديك صلاحية لتنفيذ هذا الإجراء.'),
            $e instanceof ThrottleRequestsException => self::json(429, 'rate_limited', 'محاولات كثيرة. حاول مرة أخرى بعد قليل.', [], self::stringHeaders($e->getHeaders())),
            $e instanceof HttpExceptionInterface => match ($e->getStatusCode()) {
                404 => self::json(404, 'not_found', 'العنصر المطلوب غير موجود.'),
                403 => self::json(403, 'forbidden', 'ليست لديك صلاحية لتنفيذ هذا الإجراء.'),
                405 => self::json(405, 'method_not_allowed', 'تعذّر تنفيذ الطلب.'),
                default => self::json($e->getStatusCode(), 'http_error', 'تعذّر تنفيذ الطلب.', [], self::stringHeaders($e->getHeaders())),
            },
            default => self::json(500, 'server_error', 'حدث خطأ غير متوقع. حاول مرة أخرى.'),
        };
    }

    /**
     * @param  array<string, mixed>  $details
     * @param  array<string, string>  $headers
     */
    private static function json(int $status, string $code, string $message, array $details = [], array $headers = []): JsonResponse
    {
        $error = ['code' => $code, 'message' => $message];
        if ($details !== []) {
            $error['details'] = $details;
        }

        return new JsonResponse(['error' => $error], $status, $headers);
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, string>
     */
    private static function stringHeaders(array $headers): array
    {
        return array_map(static fn (mixed $v): string => is_array($v) ? implode(', ', $v) : (string) $v, $headers);
    }
}
