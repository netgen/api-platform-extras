<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\DependencyInjection\CompilerPass;

use Netgen\ApiPlatformExtras\OpenApi\Factory\OpenApiFactory;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class SchemaProcessorCompilerPass implements CompilerPassInterface
{
    private const FEATURE_ENABLED_PARAMETER = 'netgen_api_platform_extras.features.schema_processor.enabled';

    public function process(ContainerBuilder $container): void
    {
        if (
            !$container->hasParameter(self::FEATURE_ENABLED_PARAMETER)
            || $container->getParameter(self::FEATURE_ENABLED_PARAMETER) === false
        ) {
            return;
        }

        $container
            ->setDefinition(
                OpenApiFactory::class,
                new Definition(OpenApiFactory::class),
            )
            ->setArguments([
                new Reference('api_platform.openapi.factory.inner'),
                new TaggedIteratorArgument('netgen_api_platform_extras.open_api_processor'),
            ])
            ->setDecoratedService('api_platform.openapi.factory', 'api_platform.openapi.factory.inner', -25);
    }
}
