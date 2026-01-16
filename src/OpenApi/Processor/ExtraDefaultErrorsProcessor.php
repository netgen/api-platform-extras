<?php

declare(strict_types=1);

namespace Netgen\ApiPlatformExtras\OpenApi\Processor;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactory;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\JsonSchema\SchemaUriPrefixTrait;
use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Options;
use ApiPlatform\State\ApiResource\Error as ApiResourceError;
use ArrayObject;

use function is_callable;
use function sprintf;

class ExtraDefaultErrorsProcessor implements OpenApiProcessorInterface
{
    use SchemaUriPrefixTrait;
    
    /**
     * @param array<string, array<int, string>> $errorFormats
     */
    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory,
        private readonly SchemaFactoryInterface $jsonSchemaFactory,
        private readonly Options $openApiOptions,
        private readonly array $errorFormats,
    ) {}

    public static function getPriority(): int
    {
        return 1000;
    }

    public function process(OpenApi $openApi): OpenApi
    {
        $paths = $openApi->getPaths();
        $components = $openApi->getComponents();
        if (($schemas = $components->getSchemas()) === null) {
            $schemas = new ArrayObject();
            $components = $components->withSchemas($schemas);
            $openApi = $openApi->withComponents($components);
        }

        /** @var class-string $errorResourceClass */
        $errorResourceClass = $this->openApiOptions->getErrorResourceClass() ?? ApiResourceError::class;
        $defaultError = $this->createErrorResource($errorResourceClass, 500, 'Internal Server Error');

        foreach ($paths->getPaths() as $path => $pathItem) {
            foreach (['Get', 'Post', 'Put', 'Patch', 'Delete'] as $method) {
                /** @var callable|null $getter */
                $getter = [$pathItem, 'get' . $method];

                /** @var callable|null $setter */
                $setter = [$pathItem, 'with' . $method];
                if (is_callable($getter) && is_callable($setter) && ($operation = $getter()) !== null) {
                    if ($operation instanceof Operation) {
                        $responses = $operation->getResponses();

                        if (!isset($responses[500])) {
                            $operation = $this->addOperationErrors($operation, [$defaultError->withStatus(500)->withDescription('Internal Server Error')], $schemas);
                            $pathItem = $setter($operation);
                            $paths->addPath($path, $pathItem);
                        }
                    }
                }
            }
        }

        return $openApi;
    }

    /**
     * @param ErrorResource[] $errors
     * @param ArrayObject<string, mixed> $schemas
     */
    private function addOperationErrors(
        Operation $operation,
        array $errors,
        ArrayObject $schemas,
    ): Operation {
        $schema = new Schema(Schema::VERSION_OPENAPI);
        foreach ($errors as $errorResource) {
            $responseMimeTypes = $this->flattenMimeTypes($errorResource->getOutputFormats() ?? $this->errorFormats);
            foreach ($responseMimeTypes as $mime => $format) {
                if (!isset($this->errorFormats[$format])) {
                    unset($responseMimeTypes[$mime]);
                }
            }

            if (($status = $errorResource->getStatus()) === null) {
                throw new RuntimeException(sprintf('The error class "%s" has no status defined', $errorResource->getClass() ?? 'unknown'));
            }

            $operationErrorSchemas = [];
            foreach ($responseMimeTypes as $operationFormat) {
                $class = $errorResource->getClass();
                if ($class === null) {
                    throw new RuntimeException(sprintf('The error class for "%s" is not defined', $errorResource->getShortName() ?? 'unknown'));
                }

                $operationErrorSchema = $this->jsonSchemaFactory->buildSchema($class, $operationFormat, Schema::TYPE_OUTPUT, null, $schema, [SchemaFactory::OPENAPI_DEFINITION_NAME => 'Error' . $status]);
                $operationErrorSchemas[$operationFormat] = $operationErrorSchema;

                foreach ($operationErrorSchema->getDefinitions() as $key => $definition) {
                    $this->updateStatusProperty($definition, $status);
                    $schemas[$key] = $definition;
                }
            }

            $response = ($operation->getResponses()[$status] ?? new Response($errorResource->getDescription() ?? ''))
                ->withContent($this->buildContent($responseMimeTypes, $operationErrorSchemas));

            $operation = $operation->withResponse((string) $status, $response);
        }

        return $operation;
    }

    /**
     * @param array<string, string> $responseMimeTypes
     * @param array<string, Schema> $operationSchemas
     *
     * @return ArrayObject<string, MediaType>
     */
    private function buildContent(array $responseMimeTypes, array $operationSchemas): ArrayObject
    {
        /** @var ArrayObject<string, MediaType> $content */
        $content = new ArrayObject();
        foreach ($responseMimeTypes as $mimeType => $format) {
            if (($rootDefinitionKey = $operationSchemas[$format]->getRootDefinitionKey()) !== null) {
                $content[$mimeType] = new MediaType(new ArrayObject(['$ref' => sprintf('#/components/schemas/%s', $rootDefinitionKey)]));
            }
        }

        return $content;
    }

    /**
     * @param ArrayObject<string, mixed> $definition
     */
    private function updateStatusProperty(ArrayObject $definition, int $status): void
    {
        if (isset($definition['properties']['status'])) {
            $definition['properties']['status']['default'] = $status;
            $definition['properties']['status']['examples'] = [$status];
        }

        foreach (['allOf', 'anyOf', 'oneOf'] as $composition) {
            if (isset($definition[$composition])) {
                foreach ($definition[$composition] as $subSchema) {
                    if ($subSchema instanceof ArrayObject) {
                        $this->updateStatusProperty($subSchema, $status);
                    }
                }
            }
        }

        if (isset($definition['items']) && $definition['items'] instanceof ArrayObject) {
            $this->updateStatusProperty($definition['items'], $status);
        }
    }

    /**
     * @param class-string $errorResourceClass
     */
    private function createErrorResource(string $errorResourceClass, int $status, string $description): ErrorResource
    {
        try {
            /** @var ErrorResource $errorResource */
            $errorResource = $this->resourceMetadataFactory->create($errorResourceClass)[0]?->withStatus($status)?->withDescription($description) ?? new ErrorResource(description: $description, status: $status, class: $errorResourceClass);
        } catch (ResourceClassNotFoundException) {
            $errorResource = new ErrorResource(description: $description, status: $status, class: $errorResourceClass);
        }

        return $errorResource;
    }

    /**
     * @param array<string, array<int, string>> $responseFormats
     *
     * @return array<string, string>
     */
    private function flattenMimeTypes(array $responseFormats): array
    {
        $flattened = [];
        foreach ($responseFormats as $format => $mimeTypes) {
            foreach ($mimeTypes as $mimeType) {
                $flattened[$mimeType] = $format;
            }
        }

        return $flattened;
    }
}
