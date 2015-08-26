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

use InvalidArgumentException;
use ReflectionClass;

class RegisterNotificationTypes
{
    /**
     * @var array
     */
    protected $blueprints;

    /**
     * @var array
     */
    protected $serializers;

    public function __construct(array &$blueprints, array &$serializers = [])
    {
        $this->blueprints = &$blueprints;
        $this->serializers = &$serializers;
    }

    public function register($blueprint, $serializer, $enabledByDefault = [])
    {
        if (! (new ReflectionClass($blueprint))->implementsInterface('Flarum\Core\Notifications\Blueprint')) {
            throw new InvalidArgumentException('Notification blueprint ' . $blueprint
                . ' must implement Flarum\Core\Notifications\Blueprint');
        }

        $this->blueprints[$blueprint] = $enabledByDefault;

        $this->serializers[$blueprint::getType()] = $serializer;
    }
}
