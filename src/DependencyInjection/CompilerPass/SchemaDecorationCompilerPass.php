<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\DependencyInjection\CompilerPass;

use Netgen\ApiPlatformExtras\ApiPlatform\JsonSchema\Metadata\Property\PropertyMetadataFactoryDecorator;
use Netgen\ApiPlatformExtras\ApiPlatform\JsonSchema\SchemaFactoryDecorator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use function sprintf;

final class SchemaDecorationCompilerPass implements CompilerPassInterface
{
    private const string BASE_FEATURE_PATH = 'netgen_api_platform_extras.features.schema_decoration';

    public function process(ContainerBuilder $container): void
    {
        $featureEnabledParameter = sprintf('%s.enabled', self::BASE_FEATURE_PATH);
        if (
            !$container->hasParameter($featureEnabledParameter)
            || $container->getParameter($featureEnabledParameter) === false
        ) {
            return;
        }

        $jsonldUpdateSchemaParameter = sprintf('%s.jsonld_update_schema', self::BASE_FEATURE_PATH);
        if (
            $container->hasParameter($jsonldUpdateSchemaParameter)
            && $container->getParameter($jsonldUpdateSchemaParameter) === true
        ) {
            $container
                ->setDefinition('netgen.api_platform_extras.json_schema.schema_factory', new Definition(SchemaFactoryDecorator::class))
                ->setArguments([
                    new Reference('netgen.api_platform_extras.json_schema.schema_factory.inner'),
                ])
                ->setDecoratedService('api_platform.json_schema.schema_factory');
        }

        $defaultRequiredPropertiesParameter = sprintf('%s.default_required_properties', self::BASE_FEATURE_PATH);
        if (
            $container->hasParameter($defaultRequiredPropertiesParameter)
            && $container->getParameter($defaultRequiredPropertiesParameter) === true
        ) {
            $container
                ->setDefinition('netgen.api_platform_extras.metadata.property.metadata_factory', new Definition(PropertyMetadataFactoryDecorator::class))
                ->setArguments([
                    new Reference('netgen.api_platform_extras.metadata.property.metadata_factory.inner'),
                ])
                ->setDecoratedService('api_platform.metadata.property.metadata_factory', null, 19);
        }
    }
}
