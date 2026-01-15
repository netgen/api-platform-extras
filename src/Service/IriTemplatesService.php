<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\Service;

use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

use function preg_replace;

final class IriTemplatesService
{
    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private readonly ResourceNameCollectionFactoryInterface $resourceExtractor,
        private readonly RouterInterface $router,
    ) {}

    /**
     * @return array<string, string>
     */
    public function getIriTemplatesData(): array
    {
        $resourceClasses = $this->resourceExtractor->create();
        $routeCollection = $this->router->getRouteCollection();
        $iriTemplates = [];

        foreach ($resourceClasses as $class) {
            try {
                $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($class);
            } catch (ResourceClassNotFoundException) {
                continue;
            }

            /** @var \ApiPlatform\Metadata\ApiResource $resourceMetadata */
            foreach ($resourceMetadataCollection as $resourceMetadata) {
                /** @var Operations<HttpOperation> $operations */
                $operations = $resourceMetadata->getOperations();

                foreach ($operations as $operation) {
                    if (!$operation instanceof Get) {
                        continue;
                    }

                    /** @var string $operationName */
                    $operationName = $operation->getName();
                    $route = $routeCollection->get($operationName);

                    if (!$route instanceof Route) {
                        continue;
                    }

                    $iriTemplates[$resourceMetadata->getShortName()] = $this->sanitizePath($route->getPath());

                    break;
                }
            }
        }

        return $iriTemplates;
    }

    private function sanitizePath(string $path): string
    {
        return preg_replace(
            '/\.\{_format}$/',
            '',
            $path,
        ) ?? '';
    }
}
