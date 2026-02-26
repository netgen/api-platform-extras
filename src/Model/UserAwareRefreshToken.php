<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\Model;

use Gesdinet\JWTRefreshTokenBundle\Model\AbstractRefreshToken;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class UserAwareRefreshToken extends AbstractRefreshToken
{
    protected string $class;

    public static function createForUserWithTtl(string $refreshToken, UserInterface $user, int $ttl): static
    {
        /** @var static $token */
        $token = parent::createForUserWithTtl($refreshToken, $user, $ttl);

        return $token->setClass($user::class);
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function setClass(string $class): static
    {
        $this->class = $class;

        return $this;
    }
}
