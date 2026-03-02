<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\DependencyInjection\CompilerPass;

use Netgen\ApiPlatformExtras\ApiPlatform\Hydra\JsonSchema\SchemaFactoryDecorator;
use Netgen\ApiPlatformExtras\ApiPlatform\Hydra\Serializer\PartialCollectionViewNormalizerDecorator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use function sprintf;

final class HydraPaginationEnrichmentCompilerPass implements CompilerPassInterface
{
    private const string BASE_FEATURE_PATH = 'netgen_api_platform_extras.features.hydra_pagination_enrichment';

    public function process(ContainerBuilder $container): void
    {
        $featureEnabledParameter = sprintf('%s.enabled', self::BASE_FEATURE_PATH);
        if (
            !$container->hasParameter($featureEnabledParameter)
            || $container->getParameter($featureEnabledParameter) === false
            || !$container->hasDefinition('api_platform.hydra.json_schema.schema_factory')
        ) {
            return;
        }

        $container
            ->setDefinition('netgen.api_platform_extras.hydra.json_schema.schema_factory', new Definition(SchemaFactoryDecorator::class))
            ->setArguments([
                new Reference('netgen.api_platform_extras.hydra.json_schema.schema_factory.inner'),
            ])
            ->setDecoratedService('api_platform.hydra.json_schema.schema_factory');

        if (!$container->hasDefinition('api_platform.hydra.normalizer.partial_collection_view')) {
            return;
        }

        $container
            ->setDefinition('netgen.api_platform_extras.hydra.normalizer.partial_collection_view', new Definition(PartialCollectionViewNormalizerDecorator::class))
            ->setArguments([
                new Reference('netgen.api_platform_extras.hydra.normalizer.partial_collection_view.inner'),
            ])
            ->setDecoratedService('api_platform.hydra.normalizer.partial_collection_view');
    }
}
