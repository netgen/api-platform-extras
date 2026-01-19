<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras;

use Netgen\ApiPlatformExtras\DependencyInjection\CompilerPass\IriTemplateGeneratorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class NetgenApiPlatformExtrasBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(
            new IriTemplateGeneratorCompilerPass(),
        );
    }
}
