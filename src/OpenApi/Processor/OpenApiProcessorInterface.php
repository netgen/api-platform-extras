<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\OpenApi\Processor;

use ApiPlatform\OpenApi\OpenApi;

interface OpenApiProcessorInterface
{
    /**
     * Used in compiler pass to set the tagged items service priority.
     */
    public static function getPriority(): int;

    /**
     * Process the OpenAPI specification.
     * Can modify schemas, paths, operations, or any other part of the spec.
     */
    public function process(OpenApi $openApi): OpenApi;
}
