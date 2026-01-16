<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\DependencyInjection\CompilerPass;

use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\OpenApi\Options;
use Netgen\ApiPlatformExtras\OpenApi\Factory\OpenApiFactory;
use Netgen\ApiPlatformExtras\OpenApi\Processor\ExtraDefaultErrorsProcessor;
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
                ExtraDefaultErrorsProcessor::class,
                new Definition(ExtraDefaultErrorsProcessor::class),
            )
            ->setArguments([
                new Reference(ResourceMetadataCollectionFactoryInterface::class),
                new Reference(SchemaFactoryInterface::class),
                new Reference(Options::class),
                $container->getParameter('api_platform.error_formats'),
            ])
            ->addTag('netgen_api_platform_extras.open_api_processor');

        $container
            ->setDefinition(
                OpenApiFactory::class,
                new Definition(OpenApiFactory::class),
            )
            ->setArguments([
                new Reference('api_platform.openapi.factory.inner'),
                new TaggedIteratorArgument(
                    tag: 'netgen_api_platform_extras.open_api_processor',
                    defaultPriorityMethod: 'getPriority',
                ),
            ])
            ->setDecoratedService('api_platform.openapi.factory', 'api_platform.openapi.factory.inner', -25);
    }
}
