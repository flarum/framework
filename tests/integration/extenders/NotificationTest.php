<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Driver\NotificationDriverInterface;
use Flarum\Notification\Notification;

class NotificationTest extends \Flarum\Tests\integration\TestCase
{
    /**
     * @test
     */
    public function notification_type_doesnt_exist_by_default()
    {
        $this->assertArrayNotHasKey('customNotificationType', Notification::getSubjectModels());
    }

    /**
     * @test
     */
    public function notification_driver_doesnt_exist_by_default()
    {
        $this->assertArrayNotHasKey('customNotificationDriver', Notification::getNotificationDrivers());
    }

    /**
     * @test
     */
    public function notification_type_exists_if_added()
    {
        $this->extend((new Extend\Notification)->type(
            CustomNotificationType::class,
            'customNotificationTypeSerializer'
        ));

        $this->app();

        $this->assertArrayHasKey('customNotificationType', Notification::getSubjectModels());
    }

    /**
     * @test
     */
    public function notification_driver_exists_if_added()
    {
        $this->extend((new Extend\Notification())->driver(
            'customNotificationDriver',
            CustomNotificationDriver::class
        ));

        $this->app();

        $this->assertArrayHasKey('customNotificationDriver', Notification::getNotificationDrivers());
    }
}

class CustomNotificationType implements BlueprintInterface
{
    public function getFromUser()
    {
        // ...
    }

    public function getSubject()
    {
        // ...
    }

    public function getData()
    {
        // ...
    }

    public static function getType()
    {
        return 'customNotificationType';
    }

    public static function getSubjectModel()
    {
        return 'customNotificationTypeSubjectModel';
    }
}

class CustomNotificationDriver implements NotificationDriverInterface
{
    public function send(BlueprintInterface $blueprint, array $users): void
    {
        // ...
    }

    public function addUserPreference(string $blueprintClass, bool $default): void
    {
        // ...
    }
}
