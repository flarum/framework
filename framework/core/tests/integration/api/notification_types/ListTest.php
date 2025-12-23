<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\notification_types;

use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Notification\AlertableInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\MailableInterface;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class ListTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    #[Test]
    public function guest_cannot_list_notification_types()
    {
        $response = $this->send(
            $this->request('GET', '/api/notification-types')
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function normal_user_cannot_list_notification_types()
    {
        $response = $this->send(
            $this->request('GET', '/api/notification-types', ['authenticatedAs' => 2])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function admin_can_list_notification_types()
    {
        $response = $this->send(
            $this->request('GET', '/api/notification-types', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
        $this->assertGreaterThan(0, count($data['data']));
    }

    #[Test]
    public function notification_type_has_correct_structure()
    {
        $response = $this->send(
            $this->request('GET', '/api/notification-types', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);
        $firstType = $data['data'][0];

        $this->assertArrayHasKey('type', $firstType);
        $this->assertEquals('notification-types', $firstType['type']);
        $this->assertArrayHasKey('id', $firstType);
        $this->assertArrayHasKey('attributes', $firstType);

        $attributes = $firstType['attributes'];
        $this->assertArrayHasKey('type', $attributes);
        $this->assertArrayHasKey('blueprintClass', $attributes);
        $this->assertArrayHasKey('subjectModel', $attributes);
        $this->assertArrayHasKey('defaultDrivers', $attributes);
        $this->assertArrayHasKey('capabilities', $attributes);
        $this->assertArrayHasKey('extension', $attributes);

        $capabilities = $attributes['capabilities'];
        $this->assertArrayHasKey('alert', $capabilities);
        $this->assertArrayHasKey('email', $capabilities);
        $this->assertIsBool($capabilities['alert']);
        $this->assertIsBool($capabilities['email']);
    }

    #[Test]
    public function custom_notification_types_are_listed()
    {
        $this->extend(
            (new Extend\Notification())
                ->type(TestNotificationBlueprint::class, ['alert', 'email'])
        );

        $response = $this->send(
            $this->request('GET', '/api/notification-types', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);

        $types = array_column($data['data'], 'id');
        $this->assertContains('testNotification', $types);

        // Find our custom notification
        $customNotification = null;
        foreach ($data['data'] as $type) {
            if ($type['id'] === 'testNotification') {
                $customNotification = $type;
                break;
            }
        }

        $this->assertNotNull($customNotification);
        $this->assertEquals('testNotification', $customNotification['attributes']['type']);
        $this->assertEquals(TestNotificationBlueprint::class, $customNotification['attributes']['blueprintClass']);
        $this->assertEquals(Post::class, $customNotification['attributes']['subjectModel']);
        $this->assertEquals(['alert', 'email'], $customNotification['attributes']['defaultDrivers']);
        $this->assertTrue($customNotification['attributes']['capabilities']['alert']);
        $this->assertTrue($customNotification['attributes']['capabilities']['email']);
    }
}

class TestNotificationBlueprint implements BlueprintInterface, AlertableInterface, MailableInterface
{
    public function __construct(
        protected Post $post
    ) {
    }

    public function getFromUser(): ?User
    {
        return $this->post->user;
    }

    public function getSubject(): ?AbstractModel
    {
        return $this->post;
    }

    public function getData(): mixed
    {
        return ['postId' => $this->post->id];
    }

    public static function getType(): string
    {
        return 'testNotification';
    }

    public static function getSubjectModel(): string
    {
        return Post::class;
    }

    public function getEmailViews(): array
    {
        return [
            'text' => 'flarum.forum::email.plain.test',
            'html' => 'flarum.forum::email.html.test',
        ];
    }

    public function getEmailSubject($translator): string
    {
        return 'Test Notification';
    }
}
