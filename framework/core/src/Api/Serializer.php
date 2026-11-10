<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api;

use Closure;
use Illuminate\Support\Collection;
use RuntimeException;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Resource\Resource;
use Tobyz\JsonApiServer\Schema\Field\Relationship;

use function Tobyz\JsonApiServer\has_value;
use function Tobyz\JsonApiServer\set_value;

class Serializer extends \Tobyz\JsonApiServer\Serializer
{
    private Context $context;
    private array $map = [];
    private array $primary = [];
    private Collection $deferred;

    /**
     * Whether each field of each resource in the document is visible, as its
     * first visit found.
     *
     * A resource is visited again for every include path that reaches it,
     * often on another instance of its model: a post's `discussion`, and the
     * same discussion as `mentionedBy.discussion`. Visibility depends on the
     * record and the actor, not the instance, so asking again only re-ran the
     * checks of fields already known to be hidden, on an instance that may lack
     * the relations those checks read: each one was then loaded on its own.
     *
     * @var array<string, array<string, bool>>
     */
    private array $visible = [];

    /**
     * Relationships of each resource that a visit not including them had
     * nothing to serialize for (no linkage, no meta), as any other visit that
     * doesn't include them would find.
     *
     * @var array<string, array<string, true>>
     */
    private array $emptyUnlessIncluded = [];

    /**
     * The model each resource was first visited with. A value that visit left
     * pending is not asked for again on the same model.
     *
     * @var array<string, mixed>
     */
    private array $models = [];

    public function __construct(Context $context)
    {
        $this->context = $context->withSerializer($this);
        $this->deferred = new Collection();

        parent::__construct($context);
    }

    /**
     * Add a primary resource to the document.
     */
    public function addPrimary(Resource $resource, mixed $model, array $include): void
    {
        $data = $this->addToMap($resource, $model, $include);

        $this->primary[] = $this->key($data['type'], $data['id']);
    }

    /**
     * Serialize the primary and included resources into a JSON:API resource objects.
     *
     * @return array{array[], array[]} A tuple with primary resources and included resources.
     */
    public function serialize(): array
    {
        $this->resolveDeferred();

        $keys = array_flip($this->primary);
        $primary = array_values(array_intersect_key($this->map, $keys));
        $included = array_values(array_diff_key($this->map, $keys));

        return [$primary, $included];
    }

    private function addToMap(Resource $resource, mixed $model, array $include): array
    {
        $context = $this->context->withResource($resource)->withModel($model);

        $key = $this->key($type = $resource->type(), $id = $resource->getId($model, $context));

        if (! isset($this->map[$key])) {
            $this->map[$key] = [
                'type' => $type,
                'id' => $id,
                'links' => [
                    'self' => "{$context->api->basePath}/$type/$id",
                ],
            ];
        }

        $sameModel = ($this->models[$key] ??= $model) === $model;

        foreach ($this->context->sparseFields($resource) as $field) {
            if (has_value($this->map[$key], $field)) {
                continue;
            }

            $fieldInclude = $include[$field->name] ?? null;
            $visible = $this->visible[$key][$field->name] ?? null;

            if ($visible === false) {
                continue;
            }

            if ($visible === true) {
                // An attribute that is visible but has no value yet is pending
                // from an earlier visit of this same model, which will set it.
                // Another instance of the record gets its own value as before.
                if (! $field instanceof Relationship) {
                    if ($sameModel) {
                        continue;
                    }
                } elseif ($fieldInclude === null && isset($this->emptyUnlessIncluded[$key][$field->name])) {
                    // What a relationship serializes to depends on the include
                    // it is reached with, so a visit that includes it still adds
                    // the related resources, at the depth it asks for.
                    continue;
                }
            }

            $context = $context->withField($field)->withInclude($fieldInclude);

            if ($visible === null && ! ($this->visible[$key][$field->name] = $field->isVisible($context))) {
                continue;
            }

            $value = $field->getValue($context);

            $this->whenResolved($value, function (mixed $value) use ($key, $field, $context) {
                if (
                    ($value = $field->serializeValue($value, $context)) ||
                    ! $field instanceof Relationship
                ) {
                    set_value($this->map[$key], $field, $value);
                } elseif ($context->include === null) {
                    $this->emptyUnlessIncluded[$key][$field->name] = true;
                }
            }, $field instanceof Relationship);
        }

        foreach ($resource->meta() as $field) {
            if (isset($this->map[$key]['meta'][$field->name])) {
                continue;
            }

            if (! $field->isVisible($context)) {
                continue;
            }

            $value = $field->getValue($context);

            $this->whenResolved($value, function (mixed $value) use ($key, $field, $context) {
                $this->map[$key]['meta'][$field->name] = $field->serializeValue($value, $context);
            });
        }

        return $this->map[$key];
    }

    private function key(string $type, string $id): string
    {
        return "$type:$id";
    }

    private function whenResolved($value, $callback, bool $prepend = false): void
    {
        if ($value instanceof Closure) {
            $callable = fn () => $this->whenResolved($value(), $callback);

            if ($prepend) {
                $this->deferred->prepend($callable);
            } else {
                $this->deferred->push($callable);
            }

            return;
        }

        $callback($value);
    }

    /**
     * Add an included resource to the document.
     *
     * @return array The resource identifier which can be used for linkage.
     */
    public function addIncluded(Relationship $field, $model, ?array $include): array
    {
        $relatedResource = $this->resourceForModel($field, $model);

        if ($include === null) {
            return [
                'type' => $relatedResource->type(),
                'id' => $relatedResource->getId($model, $this->context),
            ];
        }

        $data = $this->addToMap($relatedResource, $model, $include);

        return [
            'type' => $data['type'],
            'id' => $data['id'],
        ];
    }

    private function resourceForModel(Relationship $field, $model): Resource
    {
        foreach ($field->collections as $name) {
            $collection = $this->context->api->getCollection($name);

            if ($type = $collection->resource($model, $this->context)) {
                return $this->context->api->getResource($type);
            }
        }

        throw new RuntimeException(
            'No resource type defined to represent model '.get_class($model),
        );
    }

    private function resolveDeferred(): void
    {
        $i = 0;
        while ($this->deferred->count()) {
            // One level at a time. Take what has been deferred so far, and
            // let whatever it defers in turn wait for the next pass. Draining
            // the live queue instead ran each included resource's
            // relationships (which are prepended) before the next resource
            // had even been added, so every relationship below the primary
            // data was loaded through a buffer holding a single model: an
            // N+1 for each one.
            $deferred = $this->deferred;
            $this->deferred = new Collection();

            while (($resolve = $deferred->shift()) && is_callable($resolve)) {
                $resolve();
            }

            if ($i++ > 10) {
                throw new RuntimeException('Too many levels of deferred values');
            }
        }
    }
}
