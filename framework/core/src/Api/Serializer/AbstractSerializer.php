<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Closure;
use DateTime;
use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use LogicException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tobscure\JsonApi\AbstractSerializer as BaseAbstractSerializer;
use Tobscure\JsonApi\Collection;
use Tobscure\JsonApi\Relationship;
use Tobscure\JsonApi\Resource;
use Tobscure\JsonApi\SerializerInterface;

abstract class AbstractSerializer extends BaseAbstractSerializer
{
    protected Request $request;
    protected User $actor;
    protected static Container $container;

    /**
     * @var array<string, callable[]>
     */
    protected static array $attributeMutators = [];

    /**
     * @var array<string, array<string, callable>>
     */
    protected static array $customRelations = [];

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
        $this->actor = RequestUtil::getActor($request);
    }

    public function getActor(): User
    {
        return $this->actor;
    }

    public function getAttributes(mixed $model, array $fields = null): array
    {
        if (! is_object($model) && ! is_array($model)) {
            return [];
        }

        $attributes = $this->getDefaultAttributes($model);

        foreach (array_reverse(array_merge([static::class], class_parents($this))) as $class) {
            if (isset(static::$attributeMutators[$class])) {
                foreach (static::$attributeMutators[$class] as $callback) {
                    $attributes = array_merge(
                        $attributes,
                        $callback($this, $model, $attributes)
                    );
                }
            }
        }

        return $attributes;
    }

    /**
     * Get the default set of serialized attributes for a model.
     */
    abstract protected function getDefaultAttributes(object|array $model): array;

    public function formatDate(DateTime $date = null): ?string
    {
        return $date?->format(DateTime::RFC3339);
    }

    public function getRelationship($model, $name)
    {
        if ($relationship = $this->getCustomRelationship($model, $name)) {
            return $relationship;
        }

        return parent::getRelationship($model, $name);
    }

    /**
     * Get a custom relationship.
     */
    protected function getCustomRelationship(object|array $model, string $name): ?Relationship
    {
        foreach (array_merge([static::class], class_parents($this)) as $class) {
            $callback = Arr::get(static::$customRelations, "$class.$name");

            if (is_callable($callback)) {
                $relationship = $callback($this, $model);

                if (isset($relationship) && ! ($relationship instanceof Relationship)) {
                    throw new LogicException(
                        'GetApiRelationship handler must return an instance of '.Relationship::class
                    );
                }

                return $relationship;
            }
        }

        return null;
    }

    /**
     * Get a relationship builder for a has-one relationship.
     */
    public function hasOne(object|array $model, SerializerInterface|Closure|string $serializer, string $relation = null): ?Relationship
    {
        return $this->buildRelationship($model, $serializer, $relation);
    }

    /**
     * Get a relationship builder for a has-many relationship.
     */
    public function hasMany(object|array $model, SerializerInterface|Closure|string $serializer, string $relation = null): ?Relationship
    {
        return $this->buildRelationship($model, $serializer, $relation, true);
    }

    protected function buildRelationship(object|array $model, SerializerInterface|Closure|string $serializer, string $relation = null, bool $many = false): ?Relationship
    {
        if (is_null($relation)) {
            list(, , $caller) = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);

            $relation = $caller['function'];
        }

        $data = $this->getRelationshipData($model, $relation);

        if ($data) {
            $serializer = $this->resolveSerializer($serializer, $model, $data);

            $type = $many ? Collection::class : Resource::class;

            $element = new $type($data, $serializer);

            return new Relationship($element);
        }

        return null;
    }

    protected function getRelationshipData(object|array $model, string $relation): mixed
    {
        if (is_object($model)) {
            return $model->$relation;
        }

        return $model[$relation];
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function resolveSerializer(SerializerInterface|Closure|string $serializer, object|array $model, mixed $data): SerializerInterface
    {
        if ($serializer instanceof Closure) {
            $serializer = call_user_func($serializer, $model, $data);
        }

        if (is_string($serializer)) {
            $serializer = $this->resolveSerializerClass($serializer);
        }

        if (! ($serializer instanceof SerializerInterface)) {
            throw new InvalidArgumentException('Serializer must be an instance of '
                .SerializerInterface::class);
        }

        return $serializer;
    }

    protected function resolveSerializerClass(string $class): object
    {
        $serializer = static::$container->make($class);

        $serializer->setRequest($this->request);

        return $serializer;
    }

    public static function getContainer(): Container
    {
        return static::$container;
    }

    /**
     * @internal
     */
    public static function setContainer(Container $container): void
    {
        static::$container = $container;
    }

    /**
     * @internal
     */
    public static function addAttributeMutator(string $serializerClass, callable $callback): void
    {
        if (! isset(static::$attributeMutators[$serializerClass])) {
            static::$attributeMutators[$serializerClass] = [];
        }

        static::$attributeMutators[$serializerClass][] = $callback;
    }

    /**
     * @internal
     */
    public static function setRelationship(string $serializerClass, string $relation, callable $callback): void
    {
        static::$customRelations[$serializerClass][$relation] = $callback;
    }
}
