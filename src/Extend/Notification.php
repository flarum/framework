<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Api\Serializer\NotificationSerializer;
use Flarum\Extension\Extension;
use Flarum\Notification\MailableInterface;
use Flarum\Notification\Notification as NotificationModel;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use ReflectionClass;

class Notification implements ExtenderInterface
{
    private $blueprints = [];
    private $serializers = [];

    public function type(string $blueprint, string $serializer, array $channelsEnabledByDefault = [])
    {
        $this->blueprints[$blueprint] = $channelsEnabledByDefault;
        $this->serializers[$blueprint::getType()] = $serializer;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('flarum.notification.blueprints', function ($existingBlueprints) {
            return array_merge($existingBlueprints, $this->blueprints);
        });

        $container->extend('flarum.api.notification_serializers', function ($existingSerializers) {
            return array_merge($existingSerializers, $this->serializers);
        });
    }
}
