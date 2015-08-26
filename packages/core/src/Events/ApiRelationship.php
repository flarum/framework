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

class ApiRelationship
{
    /**
     * @var Serializer
     */
    public $serializer;

    /**
     * @var string
     */
    public $relationship;

    /**
     * @param Serializer $serializer
     * @param string $relationship
     */
    public function __construct(Serializer $serializer, $relationship)
    {
        $this->serializer = $serializer;
        $this->relationship = $relationship;
    }
}
