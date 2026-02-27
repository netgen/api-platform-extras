<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras;

use Netgen\ApiPlatformExtras\DependencyInjection\CompilerPass\HydraPaginationEnrichmentCompilerPass;
use Netgen\ApiPlatformExtras\DependencyInjection\CompilerPass\IriTemplateGeneratorCompilerPass;
use Netgen\ApiPlatformExtras\DependencyInjection\CompilerPass\JwtRefreshCompilerPass;
use Netgen\ApiPlatformExtras\DependencyInjection\CompilerPass\SchemaDecorationCompilerPass;
use Netgen\ApiPlatformExtras\DependencyInjection\CompilerPass\SchemaProcessorCompilerPass;
use Netgen\ApiPlatformExtras\OpenApi\Processor\OpenApiProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use function dirname;

final class NetgenApiPlatformExtrasBundle extends Bundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        $container
        ->addCompilerPass(
            new IriTemplateGeneratorCompilerPass(),
        )
        ->addCompilerPass(
            new SchemaProcessorCompilerPass(),
        )
        ->addCompilerPass(
            new SchemaDecorationCompilerPass(),
        )
        ->addCompilerPass(
            new HydraPaginationEnrichmentCompilerPass(),
        )
        ->addCompilerPass(
            new JwtRefreshCompilerPass(),
        );

        $container->registerForAutoconfiguration(OpenApiProcessorInterface::class)
            ->addTag('netgen_api_platform_extras.open_api_processor');
    }
}
