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
use Flarum\Notification\NotificationSyncer;
use Flarum\Tests\integration\TestCase;

class NotificationTest extends TestCase
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
    public function notification_serializer_doesnt_exist_by_default()
    {
        $this->app();

        $this->assertNotContains(
            'customNotificationTypeSerializer',
            $this->app->getContainer()->make('flarum.api.notification_serializers')
        );
    }

    /**
     * @test
     */
    public function notification_driver_doesnt_exist_by_default()
    {
        $this->assertArrayNotHasKey('customNotificationDriver', NotificationSyncer::getNotificationDrivers());
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
    public function notification_serializer_exists_if_added()
    {
        $this->extend((new Extend\Notification)->type(
            CustomNotificationType::class,
            'customNotificationTypeSerializer'
        ));

        $this->app();

        $this->assertContains(
            'customNotificationTypeSerializer',
            $this->app->getContainer()->make('flarum.api.notification_serializers')
        );
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

        $this->assertArrayHasKey('customNotificationDriver', NotificationSyncer::getNotificationDrivers());
    }

    /**
     * @test
     */
    public function notification_driver_enabled_types_exist_if_added()
    {
        $this->extend(
            (new Extend\Notification())
                ->type(CustomNotificationType::class, 'customSerializer')
                ->type(SecondCustomNotificationType::class, 'secondCustomSerializer', ['customDriver'])
                ->type(ThirdCustomNotificationType::class, 'thirdCustomSerializer')
                ->driver('customDriver', CustomNotificationDriver::class, [CustomNotificationType::class])
                ->driver('secondCustomDriver', SecondCustomNotificationDriver::class, [SecondCustomNotificationType::class])
        );

        $this->app();

        $blueprints = $this->app->getContainer()->make('flarum.notification.blueprints');

        $this->assertContains('customDriver', $blueprints[CustomNotificationType::class]);
        $this->assertCount(1, $blueprints[CustomNotificationType::class]);
        $this->assertContains('customDriver', $blueprints[SecondCustomNotificationType::class]);
        $this->assertContains('secondCustomDriver', $blueprints[SecondCustomNotificationType::class]);
        $this->assertEmpty($blueprints[ThirdCustomNotificationType::class]);
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

class SecondCustomNotificationType extends CustomNotificationType
{
    public static function getType()
    {
        return 'secondCustomNotificationType';
    }
}

class ThirdCustomNotificationType extends CustomNotificationType
{
    public static function getType()
    {
        return 'thirdCustomNotificationType';
    }
}

class CustomNotificationDriver implements NotificationDriverInterface
{
    public function send(BlueprintInterface $blueprint, array $users): void
    {
        // ...
    }

    public function registerType(string $blueprintClass, bool $default): void
    {
        // ...
    }
}

class SecondCustomNotificationDriver extends CustomNotificationDriver
{
    // ...
}
