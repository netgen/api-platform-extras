<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\ApiPlatform\Hydra\JsonSchema;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryAwareInterface;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\Operation;

use function is_array;

final class SchemaFactoryDecorator implements SchemaFactoryInterface, SchemaFactoryAwareInterface
{
    private const string HYDRA_COLLECTION_BASE_SCHEMA_NAME = 'HydraCollectionBaseSchema';

    private const array HYDRA_VIEW_KEYS = ['hydra:view', 'view'];

    private const array PAGINATION_PROPERTIES = [
        'firstPage' => [
            'type' => 'integer',
            'minimum' => 0,
        ],
        'lastPage' => [
            'type' => 'integer',
            'minimum' => 0,
        ],
        'currentPage' => [
            'type' => 'integer',
            'minimum' => 0,
        ],
        'previousPage' => [
            'type' => 'integer',
            'minimum' => 0,
        ],
        'nextPage' => [
            'type' => 'integer',
            'minimum' => 0,
        ],
        'itemsPerPage' => [
            'type' => 'integer',
            'minimum' => 0,
        ],
    ];

    private const array PAGINATION_EXAMPLE_VALUES = [
        'firstPage' => 1,
        'lastPage' => 10,
        'currentPage' => 1,
        'previousPage' => 1,
        'nextPage' => 2,
        'itemsPerPage' => 30,
    ];

    public function __construct(
        private readonly SchemaFactoryInterface $decorated,
    ) {}

    public function setSchemaFactory(SchemaFactoryInterface $schemaFactory): void
    {
        if ($this->decorated instanceof SchemaFactoryAwareInterface) {
            $this->decorated->setSchemaFactory($schemaFactory);
        }
    }

    /** @param array<string, mixed>|null $serializerContext */
    public function buildSchema(string $className, string $format = 'json', string $type = Schema::TYPE_OUTPUT, ?Operation $operation = null, ?Schema $schema = null, ?array $serializerContext = null, bool $forceCollection = false): Schema
    {
        $schema = $this->decorated->buildSchema($className, $format, $type, $operation, $schema, $serializerContext, $forceCollection);

        if ('jsonld' !== $format) {
            return $schema;
        }

        $definitions = $schema->getDefinitions();
        $collectionBaseSchema = $definitions[self::HYDRA_COLLECTION_BASE_SCHEMA_NAME] ?? null;

        if (!is_array($collectionBaseSchema)) {
            return $schema;
        }

        $allOf = $collectionBaseSchema['allOf'] ?? null;
        if (!is_array($allOf) || !isset($allOf[1]) || !is_array($allOf[1])) {
            return $schema;
        }

        $properties = $allOf[1]['properties'] ?? null;
        if (!is_array($properties)) {
            return $schema;
        }

        if (
            isset($properties['view']['properties']['firstPage'])
            || isset($properties['hydra:view']['properties']['firstPage'])
        ) {
            return $schema;
        }

        foreach (self::HYDRA_VIEW_KEYS as $viewKey) {
            $viewSchema = $properties[$viewKey] ?? null;
            if (!is_array($viewSchema)) {
                continue;
            }

            $viewProperties = $viewSchema['properties'] ?? [];
            if (!is_array($viewProperties)) {
                continue;
            }

            foreach (self::PAGINATION_PROPERTIES as $propertyName => $propertySchema) {
                $viewProperties[$propertyName] ??= $propertySchema;
            }

            $viewSchema['properties'] = $viewProperties;
            $viewExample = $viewSchema['example'] ?? [];
            if (is_array($viewExample)) {
                foreach (self::PAGINATION_EXAMPLE_VALUES as $propertyName => $propertyValue) {
                    $viewExample[$propertyName] ??= $propertyValue;
                }

                $viewSchema['example'] = $viewExample;
            }

            $properties[$viewKey] = $viewSchema;
        }

        $allOf[1]['properties'] = $properties;
        $collectionBaseSchema['allOf'] = $allOf;
        $definitions[self::HYDRA_COLLECTION_BASE_SCHEMA_NAME] = $collectionBaseSchema;

        return $schema;
    }
}
