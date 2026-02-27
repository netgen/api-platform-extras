<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\ApiPlatform\Hydra\Serializer;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

use function array_key_exists;
use function is_array;
use function max;
use function method_exists;
use function min;

final class PartialCollectionViewNormalizerDecorator implements NormalizerInterface, NormalizerAwareInterface
{
    public function __construct(
        private readonly NormalizerInterface $decorated,
    ) {}

    /** @param array<mixed> $context */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|\ArrayObject|bool|float|int|string|null
    {
        $normalized = $this->decorated->normalize($data, $format, $context);
        if (
            !($data instanceof PaginatorInterface)
            || !is_array($normalized)
            || $this->isCursorPaginationEnabled($context)
        ) {
            return $normalized;
        }

        $viewKey = $this->getViewKey($normalized);
        if (null === $viewKey || !is_array($normalized[$viewKey])) {
            return $normalized;
        }

        $currentPage = (int) $data->getCurrentPage();
        $lastPage = (int) $data->getLastPage();

        $normalized[$viewKey]['firstPage'] ??= 1;
        $normalized[$viewKey]['lastPage'] ??= $lastPage;
        $normalized[$viewKey]['currentPage'] ??= $currentPage;
        $normalized[$viewKey]['previousPage'] ??= max(1, $currentPage - 1);
        $normalized[$viewKey]['nextPage'] ??= (int) min($currentPage + 1, $lastPage);
        $normalized[$viewKey]['itemsPerPage'] ??= (int) $data->getItemsPerPage();

        return $normalized;
    }

    /** @param array<mixed> $context */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->decorated->supportsNormalization($data, $format, $context);
    }

    /** @return array<string, bool>|array<string, null> */
    public function getSupportedTypes(?string $format): array
    {
        if (method_exists($this->decorated, 'getSupportedTypes')) {
            /* @var array<string, bool>|array<string, null> $supportedTypes */
            return $this->decorated->getSupportedTypes($format);
        }

        return ['*' => false];
    }

    public function setNormalizer(NormalizerInterface $normalizer): void
    {
        if ($this->decorated instanceof NormalizerAwareInterface) {
            $this->decorated->setNormalizer($normalizer);
        }
    }

    /** @return array<string, bool>|array<string, null> */
    private function isCursorPaginationEnabled(array $context): bool
    {
        $operation = $context['operation'] ?? null;

        return $operation instanceof HttpOperation && $operation->getPaginationViaCursor() !== null;
    }

    /** @param array<string, mixed> $data */
    private function getViewKey(array $data): ?string
    {
        if (array_key_exists('hydra:view', $data)) {
            return 'hydra:view';
        }

        if (array_key_exists('view', $data)) {
            return 'view';
        }

        return null;
    }
}
