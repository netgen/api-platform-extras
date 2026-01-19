<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\ApiPlatform\JsonSchema;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\JsonSchema\SchemaUriPrefixTrait;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ArrayObject;

use function in_array;
use function is_string;
use function str_replace;

final class SchemaFactoryDecorator implements SchemaFactoryInterface
{
    use SchemaUriPrefixTrait;

    private const array SCHEMA_LOGICAL_OPERATORS = ['anyOf', 'oneOf', 'allOf'];

    private const string JSONLD_INPUT_OBJECT_PROPERTY_NAME = '@id';

    private const array JSONLD_INPUT_OBJECT_PROPERTY = [
        'type' => 'string',
        'format' => 'iri-reference',
        'example' => 'https://example.com/',
    ];

    public function __construct(
        private SchemaFactoryInterface $decorated,
    ) {}

    public function buildSchema(string $className, string $format = 'json', string $type = Schema::TYPE_OUTPUT, ?Operation $operation = null, ?Schema $schema = null, ?array $serializerContext = null, bool $forceCollection = false): Schema
    {
        $schema = $this->decorated->buildSchema($className, $format, $type, $operation, $schema, $serializerContext, $forceCollection);
        $version = $schema->getVersion();
        $schemaPrefix = $this->getSchemaUriPrefix($version);
        $currentReference = $schema['$ref'] ?? null;

        if (
            is_string($currentReference)
            && $type === Schema::TYPE_INPUT
            && in_array($operation::class, [Put::class, Post::class, Patch::class], true)
        ) {
            $this->ensureJsonldInputPropertyForInputSchemas($currentReference, $schemaPrefix, $schema->getDefinitions());
        }

        return $schema;
    }

    private function ensureJsonldInputPropertyForInputSchemas(string $reference, string $schemaPrefix, ArrayObject $definitions): void
    {
        $definitionName = str_replace($schemaPrefix, '', $reference);

        foreach ($definitions[$definitionName]['properties'] ?? [] as $property) {
            if (isset($property['type'])) {
                continue;
            }

            if (isset($property['$ref'])) {
                $this->addJsonldInputProperty(
                    $definitions,
                    $schemaPrefix,
                    $property['$ref'],
                );

                break;
            }

            foreach (self::SCHEMA_LOGICAL_OPERATORS as $operator) {
                if (!isset($property[$operator])) {
                    continue;
                }

                foreach ($property[$operator] as $subschema) {
                    if (!isset($subschema['$ref'])) {
                        continue;
                    }

                    $this->addJsonldInputProperty(
                        $definitions,
                        $schemaPrefix,
                        $subschema['$ref'],
                    );
                }
            }
        }
    }

    private function addJsonldInputProperty(
        ArrayObject $definitions,
        string $schemaPrefix,
        string $ref,
    ): void {
        $definitionKey = str_replace($schemaPrefix, '', $ref);

        $definitions[$definitionKey]['properties'][self::JSONLD_INPUT_OBJECT_PROPERTY_NAME]
            ??= self::JSONLD_INPUT_OBJECT_PROPERTY;
    }
}
