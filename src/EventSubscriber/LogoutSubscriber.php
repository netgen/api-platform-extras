<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\EventSubscriber;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LogoutEvent;

use function is_array;
use function is_string;
use function json_decode;

/**
 * Normalizes Gesdinet logout failure when no refresh token is provided.
 *
 * Gesdinet invalidates refresh tokens on firewall logout and can return HTTP 400
 * with message "No refresh_token found.". We convert only that response to
 * HTTP 200 so logout remains idempotent while keeping refresh deletion behavior
 * when a refresh token is actually present.
 */
final class LogoutSubscriber
{
    public function onLogout(LogoutEvent $event): void
    {
        $response = $event->getResponse();
        if (!$response instanceof JsonResponse || $response->getStatusCode() !== Response::HTTP_BAD_REQUEST) {
            return;
        }

        $content = $response->getContent();
        if (!is_string($content) || $content === '') {
            return;
        }

        $payload = json_decode($content, true);
        if (!is_array($payload) || ($payload['message'] ?? null) !== 'No refresh_token found.') {
            return;
        }

        $event->setResponse(
            new JsonResponse(
                [
                    'code' => Response::HTTP_OK,
                    'message' => 'Logged out.',
                ],
                Response::HTTP_OK,
            ),
        );
    }
}
