<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\Service;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

use function preg_replace;

final readonly class IriTemplatesService
{
    public function __construct(
        private ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private ResourceNameCollectionFactoryInterface $resourceExtractor,
        private RouterInterface $router,
    ) {}

    /**
     * @throws ResourceClassNotFoundException
     *
     * @return array<string, string>
     */
    public function getIriTemplatesData(): array
    {
        $resourceClasses = $this->resourceExtractor->create();
        $routeCollection = $this->router->getRouteCollection();
        $iriTemplates = [];

        foreach ($resourceClasses as $class) {
            /** @var ApiResource $resourceMetadata */
            foreach ($this->resourceMetadataCollectionFactory->create($class) as $resourceMetadata) {
                /** @var Operations $operations */
                $operations = $resourceMetadata->getOperations();

                foreach ($operations as $operation) {
                    if (!$operation instanceof Get) {
                        continue;
                    }

                    $route = $routeCollection->get($operation->getName());

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
        );
    }
}
