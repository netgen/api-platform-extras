<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\Gesdinet\JWTRefreshTokenBundle\Request\Extractor;

use Gesdinet\JWTRefreshTokenBundle\Request\Extractor\ExtractorInterface;
use Symfony\Component\HttpFoundation\Request;

final class RequestHeaderExtractor implements ExtractorInterface
{
    public function getRefreshToken(Request $request, string $parameter): ?string
    {
        return $request->headers->get($parameter);
    }
}
