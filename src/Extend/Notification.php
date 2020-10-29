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
    public function type(string $blueprint, string $serializer, array $enabledByDefault = [])
    {
        $type = $blueprint::getType();

        NotificationSerializer::setSubjectSerializer($type, $serializer);

        NotificationModel::setSubjectModel($type, $blueprint::getSubjectModel());

        User::addPreference(
            User::getNotificationPreferenceKey($type, 'alert'),
            'boolval',
            in_array('alert', $enabledByDefault)
        );

        if ((new ReflectionClass($blueprint))->implementsInterface(MailableInterface::class)) {
            User::addPreference(
                User::getNotificationPreferenceKey($type, 'email'),
                'boolval',
                in_array('email', $enabledByDefault)
            );
        }

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        // ...
    }
}
