<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Database\AbstractModel;
use Flarum\Extend;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Driver\NotificationDriverInterface;
use Flarum\Notification\Notification;
use Flarum\Notification\NotificationSyncer;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class NotificationTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    #[Test]
    public function notification_type_doesnt_exist_by_default()
    {
        $this->assertArrayNotHasKey('customNotificationType', Notification::getSubjectModels());
    }

    #[Test]
    public function notification_driver_doesnt_exist_by_default()
    {
        $this->assertArrayNotHasKey('customNotificationDriver', NotificationSyncer::getNotificationDrivers());
    }

    #[Test]
    public function notification_type_exists_if_added()
    {
        $this->extend((new Extend\Notification)->type(CustomNotificationType::class));

        $this->app();

        $this->assertArrayHasKey('customNotificationType', Notification::getSubjectModels());
    }

    #[Test]
    public function notification_driver_exists_if_added()
    {
        $this->extend((new Extend\Notification())->driver(
            'customNotificationDriver',
            CustomNotificationDriver::class
        ));

        $this->app();

        $this->assertArrayHasKey('customNotificationDriver', NotificationSyncer::getNotificationDrivers());
    }

    #[Test]
    public function notification_driver_enabled_types_exist_if_added()
    {
        $this->extend(
            (new Extend\Notification())
                ->type(CustomNotificationType::class)
                ->type(SecondCustomNotificationType::class, ['customDriver'])
                ->type(ThirdCustomNotificationType::class)
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

    #[Test]
    public function notification_before_sending_callback_works_if_added()
    {
        $this->extend(
            (new Extend\Notification)
                ->type(CustomNotificationType::class)
                ->driver('customNotificationDriver', CustomNotificationDriver::class)
                ->beforeSending(function ($blueprint, $users) {
                    if ($blueprint instanceof CustomNotificationType) {
                        unset($users[1]);
                    }

                    return $users;
                })
        );

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'hani']
            ],
        ]);

        $this->app();

        $users = User::whereIn('id', [1, 2, 3])->get()->all();

        $notificationSyncer = $this->app()->getContainer()->make(NotificationSyncer::class);
        $notificationSyncer->sync(new CustomNotificationType(), $users);

        $this->assertEquals('potato', $users[0]->username);
        $this->assertEquals('normal', $users[1]->username);
        $this->assertEquals('potato', $users[2]->username);
    }
}

class CustomNotificationType implements BlueprintInterface
{
    public function getFromUser(): ?User
    {
        return null;
    }

    public function getSubject(): ?AbstractModel
    {
        return null;
    }

    public function getData(): array
    {
        return [];
    }

    public static function getType(): string
    {
        return 'customNotificationType';
    }

    public static function getSubjectModel(): string
    {
        return 'customNotificationTypeSubjectModel';
    }
}

class SecondCustomNotificationType extends CustomNotificationType
{
    public static function getType(): string
    {
        return 'secondCustomNotificationType';
    }
}

class ThirdCustomNotificationType extends CustomNotificationType
{
    public static function getType(): string
    {
        return 'thirdCustomNotificationType';
    }
}

class CustomNotificationDriver implements NotificationDriverInterface
{
    public function send(BlueprintInterface $blueprint, array $users): void
    {
        foreach ($users as $user) {
            $user->username = 'potato';
        }
    }

    public function registerType(string $blueprintClass, array $driversEnabledByDefault): void
    {
        // ...
    }
}

class SecondCustomNotificationDriver extends CustomNotificationDriver
{
    // ...
}
