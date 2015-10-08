<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Relationship;

use Flarum\Core\User;
use Illuminate\Contracts\Container\Container;

trait BuilderTrait
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var string|\Closure
     */
    protected $relation;

    /**
     * @var User
     */
    protected $actor;

    /**
     * {@inheritdoc}
     */
    protected function getRelationshipData($model)
    {
        $relation = $this->relation;

        if (is_object($model)) {
            return $model->$relation;
        } elseif (is_array($model)) {
            return $model[$relation];
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function resolveSerializerClass($class)
    {
        $serializer = $this->container->make($class);

        $serializer->setActor($this->actor);

        return $serializer;
    }
}
