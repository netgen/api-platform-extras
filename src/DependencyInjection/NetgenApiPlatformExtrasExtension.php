<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

use function is_array;

class NetgenApiPlatformExtrasExtension extends Extension
{
    /**
     * @param array<int, array<string, mixed>> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);
        $this->setParameters($container, $config, $this->getAlias());
    }

    /**
     * @param mixed[] $config
     */
    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration($this);
    }

    /**
     * @param mixed[] $config
     */
    private function setParameters(
        ContainerBuilder $container,
        array $config,
        string $alias,
    ): void {
        foreach ($config as $key => $value) {
            $paramName = "{$alias}.{$key}";

            if (is_array($value)) {
                $this->setParameters($container, $value, $paramName);
            } else {
                $container->setParameter($paramName, $value);
            }
        }
    }
}
