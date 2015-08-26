<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Events;

use Flarum\Api\Serializers\Serializer;

class ApiAttributes
{
    /**
     * The class doing the serializing.
     *
     * @var Serializer
     */
    public $serializer;

    /**
     * The model being serialized.
     *
     * @var object
     */
    public $model;

    /**
     * The serialized attributes of the resource.
     *
     * @var array
     */
    public $attributes;

    /**
     * @var \Flarum\Core\Users\User
     */
    public $actor;

    /**
     * @param Serializer $serializer The class doing the serializing.
     * @param object $model The model being serialized.
     * @param array $attributes The serialized attributes of the resource.
     */
    public function __construct(Serializer $serializer, $model, array &$attributes)
    {
        $this->serializer = $serializer;
        $this->model = $model;
        $this->attributes = &$attributes;
        $this->actor = $serializer->actor;
    }
}
