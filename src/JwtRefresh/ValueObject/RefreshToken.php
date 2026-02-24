<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\JwtRefresh\ValueObject;

use Netgen\ApiPlatformExtras\JwtRefresh\TokenSourceType;

final class RefreshToken
{
    public function __construct(
        public TokenSourceType $source,
        public string $name,
        public string $value,
    ) {}

    public function getSourceToResponseMapping(): TokenSourceType
    {
        return match ($this->source) {
            TokenSourceType::Attribute, TokenSourceType::Header, TokenSourceType::Payload, TokenSourceType::Query => TokenSourceType::Header,
            TokenSourceType::Cookie => TokenSourceType::Cookie,
        };
    }
}
