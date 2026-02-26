<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\Service;

use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Cookie\JWTCookieProvider;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Netgen\ApiPlatformExtras\JwtRefresh\TokenSourceType;
use Netgen\ApiPlatformExtras\JwtRefresh\ValueObject\RefreshToken;
use Netgen\ApiPlatformExtras\Model\UserAwareRefreshToken;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Throwable;

use function array_merge;
use function is_string;
use function sprintf;
use function time;

final class TokenRefreshService
{
    /**
     * @param ServiceLocator<UserProviderInterface<UserInterface>> $providerLocator
     * @param array<string, mixed> $refreshCookieSettings
     */
    public function __construct(
        private RefreshTokenManagerInterface $refreshTokenManager,
        private RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private JWTTokenManagerInterface $jwtTokenManager,
        private ServiceLocator $providerLocator,
        private ?JWTCookieProvider $jwtCookieProvider,
        private array $refreshCookieSettings,
        private string $jwtHeaderPrefix,
        private bool $refreshSingleUse,
        private string $jwtHeaderName,
        private string $jwtCookieName,
        private int $refreshTtl,
        private bool $userAware,
    ) {
        $this->refreshCookieSettings = array_merge([
            'enabled' => false,
            'same_site' => 'lax',
            'path' => '/',
            'domain' => null,
            'http_only' => true,
            'secure' => true,
            'remove_token_from_body' => true,
            'partitioned' => false,
        ], $this->refreshCookieSettings);
    }

    public function refresh(
        Request $request,
        RefreshToken $token,
        bool $setHeader,
        bool $setCookie,
        ?string $providerId = null,
    ): void {
        if ($setHeader === false && $setCookie === false) {
            return;
        }

        if ($token->value === '') {
            return;
        }

        if ($setCookie === false && $token->getSourceToResponseMapping() === TokenSourceType::Cookie) {
            return;
        }

        if ($setHeader === false && $token->getSourceToResponseMapping() === TokenSourceType::Header) {
            return;
        }

        if (
            $providerId === null
            || $providerId === ''
            || !$this->providerLocator->has($providerId)
        ) {
            return;
        }

        $provider = $this->providerLocator->get($providerId);

        $refreshToken = $this->refreshTokenManager->get($token->value);
        if (!$refreshToken instanceof RefreshTokenInterface || !$refreshToken->isValid()) {
            return;
        }

        if ($this->userAware && !$refreshToken instanceof UserAwareRefreshToken) {
            return;
        }

        if ($this->userAware && !$provider->supportsClass($refreshToken->getClass())) {
            return;
        }

        $username = $refreshToken->getUsername();

        if (!is_string($username) || $username === '') {
            return;
        }

        try {
            $user = $provider->loadUserByIdentifier($username);
        } catch (Throwable) {
            return;
        }

        $jwt = $this->jwtTokenManager->create($user);
        $refreshTokenValue = $refreshToken->getRefreshToken();
        if (!is_string($refreshTokenValue) || $refreshTokenValue === '') {
            return;
        }

        if ($this->refreshSingleUse) {
            $this->refreshTokenManager->delete($refreshToken);

            $rotatedRefreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, $this->refreshTtl);
            $this->refreshTokenManager->save($rotatedRefreshToken);

            $rotatedValue = $rotatedRefreshToken->getRefreshToken();
            if (is_string($rotatedValue) && $rotatedValue !== '') {
                $refreshTokenValue = $rotatedValue;
            }
        }

        $response = new Response();

        $this->handleRefresh($token->getSourceToResponseMapping(), $request, $response, $token, $jwt, $refreshTokenValue);

        $response->sendHeaders();
    }

    private function createJwtCookie(string $jwt): ?Cookie
    {
        if (!$this->jwtCookieProvider instanceof JWTCookieProvider) {
            return null;
        }

        return $this->jwtCookieProvider->createCookie($jwt);
    }

    private function createRefreshCookie(string $name, string $tokenValue): Cookie
    {
        return new Cookie(
            $name,
            $tokenValue,
            time() + $this->refreshTtl,
            $this->refreshCookieSettings['path'],
            $this->refreshCookieSettings['domain'],
            $this->refreshCookieSettings['secure'],
            $this->refreshCookieSettings['http_only'],
            false,
            $this->refreshCookieSettings['same_site'],
            $this->refreshCookieSettings['partitioned'],
        );
    }

    private function handleRefresh(
        TokenSourceType $responseType,
        Request $request,
        Response $response,
        RefreshToken $token,
        string $jwtVal,
        string $refreshVal,
    ): void {
        switch ($responseType) {
            case TokenSourceType::Header:
                $headerValue = $this->jwtHeaderPrefix !== '' ? sprintf('%s %s', $this->jwtHeaderPrefix, $jwtVal) : $jwtVal;
                $request->headers->set($this->jwtHeaderName, $headerValue);
                $response->headers->set($this->jwtHeaderName, $headerValue);
                $request->headers->set($token->name, $refreshVal);
                $response->headers->set($token->name, $refreshVal);

                break;

            case TokenSourceType::Cookie:
                $request->cookies->set($this->jwtCookieName, $jwtVal);
                $jwtCookie = $this->createJwtCookie($jwtVal);
                if ($jwtCookie !== null) {
                    $response->headers->setCookie($jwtCookie);
                }
                $request->cookies->set($token->name, $refreshVal);
                $response->headers->setCookie($this->createRefreshCookie($token->name, $refreshVal));

                break;

            default:
                break;
        }
    }
}
