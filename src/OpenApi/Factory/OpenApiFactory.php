<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\OpenApi\Factory;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;

use function iterator_to_array;
use function usort;

final class OpenApiFactory implements OpenApiFactoryInterface
{
    /**
     * @param iterable<\Netgen\ApiPlatformExtras\OpenApi\Processor\OpenApiProcessorInterface> $processors
     */
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        private iterable $processors,
    ) {
        $this->processors = $this->sortProcessors($processors);
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        return $this->applyProcessors($openApi);
    }

    private function applyProcessors(OpenApi $openApi): OpenApi
    {
        foreach ($this->processors as $processor) {
            $openApi = $processor->process($openApi);
        }

        return $openApi;
    }

    /**
     * @return iterable<\Netgen\ApiPlatformExtras\OpenApi\Processor\OpenApiProcessorInterface>
     */
    private function sortProcessors(iterable $processors): array
    {
        $processors = iterator_to_array($processors);

        usort(
            $processors,
            static fn ($a, $b): int => $b->getPriority() <=> $a->getPriority(),
        );

        return $processors;
    }
}
