<?php

namespace Tobyz\JsonApiServer\Schema\Field;

use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Endpoint\Concerns\FindsResources;
use Tobyz\JsonApiServer\Exception\BadRequestException;
use Tobyz\JsonApiServer\Schema\Concerns\HasMeta;

abstract class Relationship extends Field
{
    use HasMeta;
    use FindsResources;

    public array $collections;
    public bool $includable = false;
    public bool $linkage = false;

    /**
     * Set the collection(s) that this relationship is to.
     */
    public function collection(string|array $type): static
    {
        $this->collections = (array) $type;

        return $this;
    }

    /**
     * Set the collection(s) that this relationship is to.
     */
    public function type(string|array $type): static
    {
        return $this->collection($type);
    }

    /**
     * Allow this relationship to be included.
     */
    public function includable(): static
    {
        $this->includable = true;

        return $this;
    }

    /**
     * Include linkage for this relationship.
     */
    public function withLinkage(): static
    {
        $this->linkage = true;

        return $this;
    }

    /**
     * Don't include linkage for this relationship.
     */
    public function withoutLinkage(): static
    {
        $this->linkage = false;

        return $this;
    }

    protected function findResourceForIdentifier(array $identifier, Context $context): mixed
    {
        if (!isset($identifier['type'])) {
            throw new BadRequestException('type not specified');
        }

        if (!isset($identifier['id'])) {
            throw new BadRequestException('id not specified');
        }

        $collections = array_map(
            fn($collection) => $context->api->getCollection($collection),
            $this->collections,
        );

        foreach ($collections as $collection) {
            if (in_array($identifier['type'], $collection->resources())) {
                return $this->findResource(
                    $context->withCollection($collection),
                    $identifier['id'],
                );
            }
        }

        throw new BadRequestException("type [{$identifier['type']}] not allowed", [
            'pointer' => '/type',
        ]);
    }
}
