<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\JwtRefresh;

enum TokenSourceType: string
{
    case Cookie = 'cookie';
    case Header = 'header';
    case Query = 'query';
    case Attribute = 'attribute';
    case Payload = 'payload';
}
