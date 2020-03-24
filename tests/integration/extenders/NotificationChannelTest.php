<?php

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend\NotificationChannel;
use Flarum\Extend\UserPreferences;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;

class NotificationChannelTest extends TestCase
{
    use RetrievesAuthorizedUsers;


    public function setUp()
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
               $this->normalUser()
            ]
        ]);
    }

    private function add_channel()
    {
        $this->extend(new NotificationChannel('test'));
    }

    /**
     * @test
     */
    public function can_add_notification_channel()
    {
        $this->add_channel();

        /** @var User $user */
        $user = User::find(2);

    }
}
