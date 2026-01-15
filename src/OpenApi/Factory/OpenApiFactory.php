<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\OpenApi\Factory;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use Netgen\ApiPlatformExtras\OpenApi\Processor\OpenApiProcessorInterface;

final readonly class OpenApiFactory implements OpenApiFactoryInterface
{
    /**
     * @param iterable<OpenApiProcessorInterface> $processors
     */
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        private iterable $processors,
    ) {}

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
}
