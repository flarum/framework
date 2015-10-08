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

use InvalidArgumentException;
use ReflectionClass;

class ConfigureNotificationTypes
{
    /**
     * @var array
     */
    private $blueprints;

    /**
     * @var array
     */
    private $serializers;

    public function __construct(array &$blueprints, array &$serializers = [])
    {
        $this->blueprints = &$blueprints;
        $this->serializers = &$serializers;
    }

    public function add($blueprint, $serializer, $enabledByDefault = [])
    {
        if (! (new ReflectionClass($blueprint))->implementsInterface('Flarum\Core\Notification\Blueprint')) {
            throw new InvalidArgumentException('Notification blueprint ' . $blueprint
                . ' must implement Flarum\Core\Notification\Blueprint');
        }

        $this->blueprints[$blueprint] = $enabledByDefault;

        $this->serializers[$blueprint::getType()] = $serializer;
    }
}
