<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\Api\Serializer\AbstractSerializer;

/**
 * Get an API serializer relationship.
 *
 * This event is fired when a relationship is to be included on an API document.
 * If a handler wishes to control the given relationship, then it should return
 * an instance of `Tobscure\JsonApi\Relationship\BuilderInterface`.
 *
 * @see AbstractSerializer::hasOne()
 * @see AbstractSerializer::hasMany()
 * @see https://github.com/tobscure/json-api
 */
class GetApiRelationship
{
    /**
     * @var AbstractSerializer
     */
    public $serializer;

    /**
     * @var string
     */
    public $relationship;

    /**
     * @param AbstractSerializer $serializer
     * @param string $relationship
     */
    public function __construct(AbstractSerializer $serializer, $relationship)
    {
        $this->serializer = $serializer;
        $this->relationship = $relationship;
    }
}
