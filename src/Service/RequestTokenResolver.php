<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\Service;

use Gesdinet\JWTRefreshTokenBundle\Request\Extractor\ExtractorInterface;
use Netgen\ApiPlatformExtras\JwtRefresh\TokenSourceType;
use Netgen\ApiPlatformExtras\JwtRefresh\ValueObject\RefreshToken;
use Symfony\Component\HttpFoundation\Request;

final readonly class RequestTokenResolver
{
    public function __construct(
        private ExtractorInterface $extractor,
        private string $refreshTokenName,
    ) {}

    public function resolveRefreshTokens(Request $request): ?RefreshToken
    {
        $value = $this->extractor->getRefreshToken($request, $this->refreshTokenName);

        if ($value === null) {
            return null;
        }

        $source = $this->detectSource($request);

        return $source
            ? new RefreshToken($source, $this->refreshTokenName, $value)
            : null;
    }

    private function detectSource(Request $request): ?TokenSourceType
    {
        return match (true) {
            $request->cookies->has($this->refreshTokenName) => TokenSourceType::Cookie,
            $request->headers->has($this->refreshTokenName) => TokenSourceType::Header,
            $request->request->has($this->refreshTokenName) => TokenSourceType::Payload,
            $request->query->has($this->refreshTokenName) => TokenSourceType::Query,
            $request->attributes->has($this->refreshTokenName) => TokenSourceType::Attribute,
            default => null,
        };
    }
}
