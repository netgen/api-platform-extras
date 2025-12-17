<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class NetgenApiPlatformExtrasExtension extends Extension
{
    /**
     * @param array<int, array<string, mixed>> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        if (($config['http_cache']['enabled'] ?? false) === true) {
            // TODO: register http cache related services
        }

        if (($config['schema_decoration']['enabled'] ?? false) === true) {
            // TODO: register schema decoration related services
        }

        if (($config['simple_normalizer']['enabled'] ?? false) === true) {
            // TODO: register simple normalizer related services
        }

        if (($config['jwt_refresh']['enabled'] ?? false) === true) {
            // TODO: register JWT refresh related services
        }

        if (($config['iri_template_generator']['enabled'] ?? false) === true) {
            // TODO: register iri templates generator related services
        }
    }

    /**
     * @param mixed[] $config
     */
    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration($this);
    }
}
