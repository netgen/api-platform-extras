<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\DependencyInjection\CompilerPass;

use Netgen\ApiPlatformExtras\Command\GenerateIriTemplatesCommand;
use Netgen\ApiPlatformExtras\Service\IriTemplatesService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class IriTemplateGeneratorCompilerPass implements CompilerPassInterface
{
    private const FEATURE_ENABLED_PARAMETER = 'netgen_api_platform_extras.features.iri_template_generator.enabled';

    public function process(ContainerBuilder $container): void
    {
        if (
            !$container->hasParameter(self::FEATURE_ENABLED_PARAMETER)
            || $container->getParameter(self::FEATURE_ENABLED_PARAMETER) === false
        ) {
            return;
        }

        $container
            ->setDefinition(
                IriTemplatesService::class,
                new Definition(IriTemplatesService::class),
            )
            ->setAutowired(true);

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
            ->setAutowired(true);
    }
}
