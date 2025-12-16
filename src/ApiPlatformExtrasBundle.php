<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class ApiPlatformExtrasBundle extends AbstractBundle
{
    protected string $extensionAlias = 'api_platform_extras';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('http_cache')
                    ->canBeDisabled()
                ->end()
                ->arrayNode('schema_decoration')
                    ->canBeDisabled()
                ->end()
                ->arrayNode('simple_normalizer')
                    ->canBeDisabled()
                ->end()
                ->arrayNode('jwt_refresh')
                    ->canBeDisabled()
                ->end()
            ->end()
        ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if (($config['http_cache']['enabled'] ?? false) === true) {
            // #TODO build http cache services
        }

        if (($config['schema_decoration']['enabled'] ?? false) === true) {
            // #TODO build schema decoration services
        }

        if (($config['simple_normalizer']['enabled'] ?? false) === true) {
            // #TODO build simple normalizer services
        }

        if (($config['jwt_refresh']['enabled'] ?? false) === true) {
            // #TODO build jwt refresh services
        }
    }
}
