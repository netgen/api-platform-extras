<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\DependencyInjection\CompilerPass;

use Netgen\ApiPlatformExtras\EventSubscriber\JwtRefreshSubscriber;
use Netgen\ApiPlatformExtras\EventSubscriber\LogoutSubscriber;
use Netgen\ApiPlatformExtras\Gesdinet\JWTRefreshTokenBundle\Request\Extractor\RequestHeaderExtractor;
use Netgen\ApiPlatformExtras\Service\RequestTokenResolver;
use Netgen\ApiPlatformExtras\Service\TokenRefreshService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Http\Event\LogoutEvent;

use function array_key_exists;
use function is_array;
use function is_scalar;
use function sprintf;
use function str_starts_with;

final class JwtRefreshCompilerPass implements CompilerPassInterface
{
    private const string BASE_FEATURE_PATH = 'netgen_api_platform_extras.features.jwt_refresh';

    public function process(ContainerBuilder $container): void
    {
        $featureEnabled = $this->resolveBoolParameter($container, sprintf('%s.enabled', self::BASE_FEATURE_PATH), false);
        $autoRefreshCookie = $this->resolveBoolParameter($container, sprintf('%s.auto_refresh_cookie', self::BASE_FEATURE_PATH), false);
        $autoRefreshHeader = $this->resolveBoolParameter($container, sprintf('%s.auto_refresh_header', self::BASE_FEATURE_PATH), false);
        if ($featureEnabled === false) {
            return;
        }

        $this->registerLogoutSubscriber($container);

        if ($autoRefreshCookie === false && $autoRefreshHeader === false) {
            return;
        }

        $refreshTokenManagerId = $this->firstAvailable($container, [
            'gesdinet_jwt_refresh_token.refresh_token_manager',
            'gesdinet.jwtrefreshtoken.refresh_token_manager',
        ]);

        $refreshTokenGeneratorId = $this->firstAvailable($container, [
            'gesdinet_jwt_refresh_token.refresh_token_generator',
            'gesdinet.jwtrefreshtoken.refresh_token_generator',
        ]);

        $refreshTokenExtractorId = $this->firstAvailable($container, [
            'gesdinet_jwt_refresh_token.request.extractor.chain',
            'gesdinet.jwtrefreshtoken.request.extractor.chain',
        ]);

        if (
            $refreshTokenExtractorId === null
            || $refreshTokenGeneratorId === null
            || $refreshTokenManagerId === null
            || !$container->has('lexik_jwt_authentication.jwt_manager')
        ) {
            return;
        }

        // Lexik token extractor config is usually applied by replacing service definition arguments,
        // not by exposing dedicated container parameters. We read parameters first and only fall back
        // to those service arguments when the parameter is missing.
        $jwtAuthorizationHeaderPrefix = $this->resolveStringParameterOrServiceArgument(
            $container,
            'lexik_jwt_authentication.token_extractors.authorization_header.prefix',
            'lexik_jwt_authentication.extractor.authorization_header_extractor',
            0,
            'Bearer',
        );
        $jwtAuthorizationHeaderName = $this->resolveStringParameterOrServiceArgument(
            $container,
            'lexik_jwt_authentication.token_extractors.authorization_header.name',
            'lexik_jwt_authentication.extractor.authorization_header_extractor',
            1,
            'Authorization',
        );
        $jwtCookieName = $this->resolveStringParameterOrServiceArgument(
            $container,
            'lexik_jwt_authentication.token_extractors.cookie.name',
            'lexik_jwt_authentication.extractor.cookie_extractor',
            0,
            'BEARER',
        );
        $refreshTokenName = $this->resolveStringParameter($container, 'gesdinet_jwt_refresh_token.token_parameter_name', 'refresh_token');
        $refreshTtl = $this->resolveIntParameter($container, 'gesdinet_jwt_refresh_token.ttl', 2592000);
        $refreshSingleUse = $this->resolveBoolParameter($container, 'gesdinet_jwt_refresh_token.single_use', false);
        $refreshCookieConfig = $this->resolveArrayParameter($container, 'gesdinet_jwt_refresh_token.cookie', []);

        $userProviderLocatorReference = ServiceLocatorTagPass::register(
            $container,
            $this->resolveUserProviderReferences($container),
        );

        $container->setDefinition(
            RequestHeaderExtractor::class,
            new Definition(RequestHeaderExtractor::class),
        );

        if ($container->hasDefinition($refreshTokenExtractorId)) {
            $container
                ->getDefinition($refreshTokenExtractorId)
                ->addMethodCall('addExtractor', [new Reference(RequestHeaderExtractor::class)]);
        } elseif ($container->hasAlias($refreshTokenExtractorId)) {
            $targetId = (string) $container->getAlias($refreshTokenExtractorId);
            if ($container->hasDefinition($targetId)) {
                $container
                    ->getDefinition($targetId)
                    ->addMethodCall('addExtractor', [new Reference(RequestHeaderExtractor::class)]);
            }
        }

        $container->setDefinition(
            RequestTokenResolver::class,
            new Definition(RequestTokenResolver::class),
        )->setArguments([
            new Reference($refreshTokenExtractorId),
            $refreshTokenName,
        ]);

        $container->setDefinition(
            TokenRefreshService::class,
            new Definition(TokenRefreshService::class),
        )->setArguments([
            new Reference($refreshTokenManagerId),
            new Reference($refreshTokenGeneratorId),
            new Reference('lexik_jwt_authentication.jwt_manager'),
            $userProviderLocatorReference,
            new Reference(
                sprintf('lexik_jwt_authentication.cookie_provider.%s', $jwtCookieName),
                ContainerInterface::NULL_ON_INVALID_REFERENCE,
            ),
            $refreshCookieConfig,
            $jwtAuthorizationHeaderPrefix,
            $refreshSingleUse,
            $jwtAuthorizationHeaderName,
            $jwtCookieName,
            $refreshTtl,
            $this->resolveBoolParameter($container, sprintf('%s.user_aware', self::BASE_FEATURE_PATH), false),
        ]);

        $container->setDefinition(
            JwtRefreshSubscriber::class,
            new Definition(JwtRefreshSubscriber::class),
        )
            ->addTag('kernel.event_subscriber')
            ->setArguments([
                new Reference('lexik_jwt_authentication.extractor.chain_extractor'),
                new Reference(RequestTokenResolver::class),
                new Reference(TokenRefreshService::class),
                new Reference('security.firewall.map'),
                $this->resolveArrayParameter($container, sprintf('%s.allowed_firewalls', self::BASE_FEATURE_PATH), []),
                $this->resolveArrayParameter($container, sprintf('%s.ignored_routes', self::BASE_FEATURE_PATH), []),
                $this->resolveArrayParameter($container, sprintf('%s.ignored_paths', self::BASE_FEATURE_PATH), []),
                $autoRefreshCookie,
                $autoRefreshHeader,
            ]);
    }

    private function resolveStringParameter(ContainerBuilder $container, string $name, string $default): string
    {
        if (!$container->hasParameter($name)) {
            return $default;
        }

        $value = $container->getParameter($name);

        return is_scalar($value) ? (string) $value : $default;
    }

    private function resolveStringParameterOrServiceArgument(
        ContainerBuilder $container,
        string $parameterName,
        string $serviceId,
        int $argumentIndex,
        string $default,
    ): string {
        if ($container->hasParameter($parameterName)) {
            return $this->resolveStringParameter($container, $parameterName, $default);
        }

        $definition = $this->resolveDefinition($container, $serviceId);
        if (!$definition instanceof Definition) {
            return $default;
        }

        $arguments = $definition->getArguments();
        if (!array_key_exists($argumentIndex, $arguments)) {
            return $default;
        }

        $value = $arguments[$argumentIndex];

        return is_scalar($value) ? (string) $value : $default;
    }

    private function resolveDefinition(ContainerBuilder $container, string $id): ?Definition
    {
        if ($container->hasDefinition($id)) {
            return $container->getDefinition($id);
        }

        if (!$container->hasAlias($id)) {
            return null;
        }

        $targetId = (string) $container->getAlias($id);

        return $container->hasDefinition($targetId) ? $container->getDefinition($targetId) : null;
    }

    private function resolveIntParameter(ContainerBuilder $container, string $name, int $default): int
    {
        if (!$container->hasParameter($name)) {
            return $default;
        }

        return (int) $container->getParameter($name);
    }

    private function resolveBoolParameter(ContainerBuilder $container, string $name, bool $default): bool
    {
        if (!$container->hasParameter($name)) {
            return $default;
        }

        return (bool) $container->getParameter($name);
    }

    /**
     * @param mixed[] $default
     *
     * @return mixed[]
     */
    private function resolveArrayParameter(ContainerBuilder $container, string $name, array $default): array
    {
        if (!$container->hasParameter($name)) {
            return $default;
        }

        $value = $container->getParameter($name);

        return is_array($value) ? $value : $default;
    }

    /**
     * @return array<string, Reference>
     */
    private function resolveUserProviderReferences(ContainerBuilder $container): array
    {
        $references = [];

        foreach ($container->getDefinitions() as $id => $_definition) {
            if (str_starts_with($id, 'security.user.provider.concrete.')) {
                $references[$id] = new Reference($id);
            }
        }

        foreach ($container->getAliases() as $id => $alias) {
            if (!str_starts_with($id, 'security.user.provider.concrete.')) {
                continue;
            }

            $targetId = (string) $alias;
            $references[$id] = new Reference($id);
            $references[$targetId] = new Reference($targetId);
        }

        return $references;
    }

    /**
     * @param string[] $ids
     */
    private function firstAvailable(ContainerBuilder $container, array $ids): ?string
    {
        foreach ($ids as $id) {
            if ($container->has($id)) {
                return $id;
            }
        }

        return null;
    }

    private function registerLogoutSubscriber(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $id => $_definition) {
            if (!str_starts_with($id, 'security.event_dispatcher.')) {
                continue;
            }

            $container->setDefinition(
                sprintf('%s.%s', LogoutSubscriber::class, $id),
                (new Definition(LogoutSubscriber::class))
                    ->addTag('kernel.event_listener', [
                        'event' => LogoutEvent::class,
                        'method' => 'onLogout',
                        'dispatcher' => $id,
                        'priority' => -128,
                    ]),
            );
        }
    }
}
