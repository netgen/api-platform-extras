<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Netgen\ApiPlatformExtras\JwtRefresh\ValueObject\RefreshToken;
use Netgen\ApiPlatformExtras\Service\RequestTokenResolver;
use Netgen\ApiPlatformExtras\Service\TokenRefreshService;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Http\FirewallMapInterface;

use function array_find;
use function in_array;
use function method_exists;
use function str_starts_with;

final class JwtRefreshSubscriber implements EventSubscriberInterface
{
    /**
     * @param string[] $allowedFirewalls
     * @param string[] $ignoredRoutes
     * @param string[] $ignoredPaths
     */
    public function __construct(
        private TokenExtractorInterface $jwtTokenExtractor,
        private RequestTokenResolver $tokensResolver,
        private TokenRefreshService $tokenRefreshService,
        private FirewallMapInterface $firewallMap,
        private array $allowedFirewalls,
        private array $ignoredRoutes,
        private array $ignoredPaths,
        private bool $cookieAutoRefreshEnabled,
        private bool $headerAutoRefreshEnabled,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->cookieAutoRefreshEnabled === false && $this->headerAutoRefreshEnabled === false) {
            return;
        }

        $request = $event->getRequest();
        $jwtToken = $this->jwtTokenExtractor->extract($request);
        if ($jwtToken !== false) {
            return;
        }

        $refreshToken = $this->tokensResolver->resolveRefreshTokens($request);
        if (!$refreshToken instanceof RefreshToken) {
            return;
        }

        if (method_exists($this->firewallMap, 'getFirewallConfig')) {
            $firewallConfig = $this->firewallMap->getFirewallConfig($request);

            if (!$firewallConfig instanceof FirewallConfig) {
                return;
            }

            if ($this->allowedFirewalls !== [] && !in_array($firewallConfig->getName(), $this->allowedFirewalls, true)) {
                return;
            }
        } else {
            return;
        }

        if (array_find(
            $this->ignoredPaths,
            static fn (string $path) => str_starts_with($request->getPathInfo(), $path),
        ) !== null) {
            return;
        }

        if (array_find(
            $this->ignoredRoutes,
            static fn (string $route) => $request->attributes->get('_route') === $route,
        ) !== null) {
            return;
        }

        $this->tokenRefreshService->refresh(
            $request,
            $refreshToken,
            $this->headerAutoRefreshEnabled,
            $this->cookieAutoRefreshEnabled,
            $firewallConfig->getProvider(),
        );
    }
}
