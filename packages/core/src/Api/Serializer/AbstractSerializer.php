<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use DateTime;
use Closure;
use Flarum\Core\User;
use Flarum\Event\PrepareApiAttributes;
use Flarum\Event\GetApiRelationship;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use LogicException;
use Tobscure\JsonApi\AbstractSerializer as BaseAbstractSerializer;
use Flarum\Api\Relationship\HasOneBuilder;
use Flarum\Api\Relationship\HasManyBuilder;
use Tobscure\JsonApi\Relationship\BuilderInterface;

abstract class AbstractSerializer extends BaseAbstractSerializer
{
    /**
     * @var User
     */
    protected $actor;

    /**
     * @var Dispatcher
     */
    protected static $dispatcher;

    /**
     * @var Container
     */
    protected static $container;

    /**
     * @return User
     */
    public function getActor()
    {
        return $this->actor;
    }

    /**
     * @param User $actor
     */
    public function setActor(User $actor)
    {
        $this->actor = $actor;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes($model, array $fields = null)
    {
        if (! is_object($model) && ! is_array($model)) {
            return [];
        }

        $attributes = $this->getDefaultAttributes($model);

        static::$dispatcher->fire(
            new PrepareApiAttributes($this, $model, $attributes)
        );

        return $attributes;
    }

    /**
     * Get the default set of serialized attributes for a model.
     *
     * @param object|array $model
     * @return array
     */
    abstract protected function getDefaultAttributes($model);

    /**
     * @param DateTime|null $date
     * @return string|null
     */
    protected function formatDate(DateTime $date = null)
    {
        if ($date) {
            return $date->format(DateTime::RFC3339);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRelationshipBuilder($name)
    {
        if ($relationship = $this->getCustomRelationship($name)) {
            return $relationship;
        }

        return parent::getRelationshipBuilder($name);
    }

    /**
     * Get a custom relationship.
     *
     * @param string $name
     * @return BuilderInterface|null
     */
    protected function getCustomRelationship($name)
    {
        $builder = static::$dispatcher->until(
            new GetApiRelationship($this, $name)
        );

        if ($builder && ! ($builder instanceof BuilderInterface)) {
            throw new LogicException('GetApiRelationship handler must return an instance of '
                . BuilderInterface::class);
        }

        return $builder;
    }

    /**
     * Get a relationship builder for a has-one relationship.
     *
     * @param string|Closure|\Tobscure\JsonApi\SerializerInterface $serializer
     * @param string|Closure|null $relation
     * @return HasOneBuilder
     */
    public function hasOne($serializer, $relation = null)
    {
        if (is_null($relation)) {
            $relation = $this->getRelationCaller();
        }

        return new HasOneBuilder($serializer, $relation, $this->actor, static::$container);
    }

    /**
     * Get a relationship builder for a has-many relationship.
     *
     * @param string|Closure|\Tobscure\JsonApi\SerializerInterface $serializer
     * @param string|Closure|null $relation
     * @return HasManyBuilder
     */
    public function hasMany($serializer, $relation = null)
    {
        if (is_null($relation)) {
            $relation = $this->getRelationCaller();
        }

        return new HasManyBuilder($serializer, $relation, $this->actor, static::$container);
    }

    /**
     * Guess the name of a relation from the stack trace.
     *
     * @return string
     */
    protected function getRelationCaller()
    {
        list(, , $caller) = debug_backtrace(false, 3);

        return $caller['function'];
    }

    /**
     * @return Dispatcher
     */
    public static function getEventDispatcher()
    {
        return static::$dispatcher;
    }

    /**
     * @param Dispatcher $dispatcher
     */
    public static function setEventDispatcher(Dispatcher $dispatcher)
    {
        static::$dispatcher = $dispatcher;
    }

    /**
     * @return Container
     */
    public static function getContainer()
    {
        return static::$container;
    }

    /**
     * @param Container $container
     */
    public static function setContainer(Container $container)
    {
        static::$container = $container;
    }
}
