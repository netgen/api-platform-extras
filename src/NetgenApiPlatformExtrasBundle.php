<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras;

use Netgen\ApiPlatformExtras\DependencyInjection\CompilerPass\SchemaProcessorCompilerPass;
use Netgen\ApiPlatformExtras\OpenApi\Processor\OpenApiProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Netgen\ApiPlatformExtras\DependencyInjection\CompilerPass\IriTemplateGeneratorCompilerPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class NetgenApiPlatformExtrasBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container
        ->addCompilerPass(
            new IriTemplateGeneratorCompilerPass(),
        )
        ->addCompilerPass(
            new SchemaProcessorCompilerPass(),
        );

        $container->registerForAutoconfiguration(OpenApiProcessorInterface::class)
            ->addTag('netgen_api_platform_extras.open_api_processor')
            ->setLazy(true);
    }
}
