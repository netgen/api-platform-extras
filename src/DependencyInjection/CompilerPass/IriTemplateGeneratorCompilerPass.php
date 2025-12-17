<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\DependencyInjection\CompilerPass;

use Netgen\ApiPlatformExtras\Command\GenerateIriTemplatesCommand;
use Netgen\ApiPlatformExtras\Service\IriTemplatesService;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class IriTemplateGeneratorCompilerPass extends FeatureCompilerPass
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->getParameter($this->getFeatureEnabledParameterPath()) === false) {
            return;
        }

        $container
            ->setDefinition(
                IriTemplatesService::class,
                new Definition(IriTemplatesService::class),
            )
            ->setAutowired(true)
            ->setPublic(true);

        $container
            ->setDefinition(
                GenerateIriTemplatesCommand::class,
                new Definition(GenerateIriTemplatesCommand::class),
            )
            ->addTag(
                'console.command',
                [
                    'name' => 'api-platform-extras:generate-iri-templates',
                    'description' => 'Generate IRI templates and write them to a JSON file',
                ],
            )
            ->setAutowired(true)
            ->setPublic(true);
    }
}
