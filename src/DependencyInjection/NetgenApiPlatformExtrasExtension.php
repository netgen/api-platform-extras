<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\DependencyInjection;

use RuntimeException;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

use function dirname;
use function in_array;
use function is_array;
use function is_file;
use function is_readable;
use function sprintf;

final class NetgenApiPlatformExtrasExtension extends Extension implements PrependExtensionInterface
{
    private const array SCALAR_ARRAY_PARAMS = [
        'ignored_routes',
        'ignored_paths',
        'allowed_firewalls',
    ];

    /**
     * @param mixed[] $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);
        $this->setParameters($container, $config, $this->getAlias());
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('doctrine')) {
            return;
        }

        $configFile = dirname(__DIR__, 2) . '/config/doctrine.yaml';

        if (!is_file($configFile) || !is_readable($configFile)) {
            return;
        }

        try {
            $config = Yaml::parseFile($configFile);
        } catch (ParseException $e) {
            throw new RuntimeException(sprintf('Could not parse YAML file "%s": %s', $configFile, $e->getMessage()), 0, $e);
        }

        if (!is_array($config)) {
            return;
        }

        $container->addResource(new FileResource($configFile));
        $container->prependExtensionConfig('doctrine', $config['doctrine']);
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

            if (is_array($value) && !in_array($key, self::SCALAR_ARRAY_PARAMS, true)) {
                $this->setParameters($container, $value, $paramName);
            } else {
                $container->setParameter($paramName, $value);
            }
        }
    }
}
