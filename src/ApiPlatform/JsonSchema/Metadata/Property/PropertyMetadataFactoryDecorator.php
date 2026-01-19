<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\ApiPlatform\JsonSchema\Metadata\Property;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Symfony\Component\TypeInfo\Type\NullableType;

final class PropertyMetadataFactoryDecorator implements PropertyMetadataFactoryInterface
{
    public function __construct(
        private PropertyMetadataFactoryInterface $decorated,
    ) {}

    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);

        $type = $propertyMetadata->getNativeType();

        if (
            ($options['schema_type'] ?? null) === Schema::TYPE_OUTPUT

            && $type !== null && $type::class !== NullableType::class
        ) {
            return $propertyMetadata->withRequired(true);
        }

        return $propertyMetadata;
    }
}
