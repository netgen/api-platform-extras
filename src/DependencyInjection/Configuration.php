<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class Configuration implements ConfigurationInterface
{
    public function __construct(private ExtensionInterface $extension) {}

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder($this->extension->getAlias());
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('http_cache')
                    ->canBeEnabled()
                ->end()
                ->arrayNode('schema_decoration')
                    ->canBeEnabled()
                ->end()
                ->arrayNode('simple_normalizer')
                    ->canBeEnabled()
                ->end()
                ->arrayNode('jwt_refresh')
                    ->canBeEnabled()
                ->end()
                ->arrayNode('iri_template_generator')
                    ->canBeEnabled()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
