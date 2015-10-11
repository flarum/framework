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
use Tobscure\JsonApi\Relationship\AbstractHasManyBuilder;

class HasManyBuilder extends AbstractHasManyBuilder
{
    use BuilderTrait;

    /**
     * @param string|\Closure|\Tobscure\JsonApi\SerializerInterface $serializer
     * @param string|\Closure $relation
     * @param User $actor
     * @param Container $container
     */
    public function __construct($serializer, $relation, User $actor, Container $container)
    {
        parent::__construct($serializer);

        $this->relation = $relation;
        $this->container = $container;
        $this->actor = $actor;
    }
}
