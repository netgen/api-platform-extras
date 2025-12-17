<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;

use function mb_strrchr;
use function mb_substr;
use function sprintf;
use function str_ends_with;

abstract class FeatureCompilerPass implements CompilerPassInterface
{
    protected const EXTENSION_ALIAS = 'netgen_api_platform_extras';

    protected function getFeatureAlias(): string
    {
        $className = mb_strrchr(static::class, '\\');
        if ($className === false || !str_ends_with($className, 'CompilerPass')) {
            throw new BadMethodCallException('This FeatureCompilerPass does not follow the naming convention; you must overwrite the getFeatureAlias() method.');
        }
        $classBaseName = mb_substr($className, 1, -12);

        return Container::underscore($classBaseName);
    }

    protected function getFeatureParameterBasePath(): string
    {
        return sprintf('%s.%s.', self::EXTENSION_ALIAS, $this->getFeatureAlias());
    }

    protected function getFeatureEnabledParameterPath(): string
    {
        return sprintf('%s%s', $this->getFeatureParameterBasePath(), 'enabled');
    }
}
