<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\notification_preview;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Notification\Blueprint\DiscussionRenamedBlueprint;
use Flarum\Post\CommentPost;
use Flarum\Post\DiscussionRenamedPost;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class ShowTest extends TestCase
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
    public function guest_cannot_preview_notifications()
    {
        $response = $this->send(
            $this->request('GET', '/api/notification-preview')
                ->withQueryParams([
                    'blueprint' => DiscussionRenamedBlueprint::class,
                ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function normal_user_cannot_preview_notifications()
    {
        $response = $this->send(
            $this->request('GET', '/api/notification-preview', ['authenticatedAs' => 2])
                ->withQueryParams([
                    'blueprint' => DiscussionRenamedBlueprint::class,
                ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function admin_can_preview_notification()
    {
        $response = $this->send(
            $this->request('GET', '/api/notification-preview', ['authenticatedAs' => 1])
                ->withQueryParams([
                    'blueprint' => DiscussionRenamedBlueprint::class,
                    'driver' => 'alert',
                ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('type', $data);
        $this->assertEquals('notification-preview', $data['type']);
        $this->assertArrayHasKey('driver', $data);
        $this->assertEquals('alert', $data['driver']);
        $this->assertArrayHasKey('content', $data);
    }

    #[Test]
    public function blueprint_parameter_is_required()
    {
        $response = $this->send(
            $this->request('GET', '/api/notification-preview', ['authenticatedAs' => 1])
        );

        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('errors', $data);
    }

    #[Test]
    public function blueprint_class_must_exist()
    {
        $response = $this->send(
            $this->request('GET', '/api/notification-preview', ['authenticatedAs' => 1])
                ->withQueryParams([
                    'blueprint' => 'NonExistentClass',
                ])
        );

        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('errors', $data);
    }

    #[Test]
    public function can_preview_alert_notification()
    {
        $response = $this->send(
            $this->request('GET', '/api/notification-preview', ['authenticatedAs' => 1])
                ->withQueryParams([
                    'blueprint' => DiscussionRenamedBlueprint::class,
                    'driver' => 'alert',
                ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals('notification-preview', $data['type']);
        $this->assertEquals('alert', $data['driver']);
        $this->assertIsArray($data['content']);
        $this->assertArrayHasKey('postNumber', $data['content']);
    }

    #[Test]
    public function email_driver_returns_subject_and_content()
    {
        $this->extend(
            (new \Flarum\Extend\Notification())
                ->type(TestMailableBlueprint::class, ['email'])
        );

        $response = $this->send(
            $this->request('GET', '/api/notification-preview', ['authenticatedAs' => 1])
                ->withQueryParams([
                    'blueprint' => TestMailableBlueprint::class,
                    'driver' => 'email-html',
                ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals('notification-preview', $data['type']);
        $this->assertEquals('email-html', $data['driver']);
        $this->assertArrayHasKey('subject', $data);
        $this->assertArrayHasKey('content', $data);
        $this->assertIsString($data['content']);
        $this->assertNotEmpty($data['content']);
    }

    #[Test]
    public function can_preview_plain_text_email()
    {
        $this->extend(
            (new \Flarum\Extend\Notification())
                ->type(TestMailableBlueprint::class, ['email'])
        );

        $response = $this->send(
            $this->request('GET', '/api/notification-preview', ['authenticatedAs' => 1])
                ->withQueryParams([
                    'blueprint' => TestMailableBlueprint::class,
                    'driver' => 'email-plain',
                ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals('notification-preview', $data['type']);
        $this->assertEquals('email-plain', $data['driver']);
        $this->assertArrayHasKey('subject', $data);
        $this->assertArrayHasKey('content', $data);
        $this->assertIsString($data['content']);
    }

    #[Test]
    public function non_mailable_blueprint_cannot_preview_email()
    {
        $response = $this->send(
            $this->request('GET', '/api/notification-preview', ['authenticatedAs' => 1])
                ->withQueryParams([
                    'blueprint' => DiscussionRenamedBlueprint::class,
                    'driver' => 'email-html',
                ])
        );

        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('errors', $data);
    }
}

class TestMailableBlueprint implements \Flarum\Notification\Blueprint\BlueprintInterface, \Flarum\Notification\MailableInterface
{
    public function __construct(
        protected \Flarum\Post\Post $post
    ) {
    }

    public function getFromUser(): ?User
    {
        return $this->post->user;
    }

    public function getSubject(): ?\Flarum\Database\AbstractModel
    {
        return $this->post;
    }

    public function getData(): mixed
    {
        return ['postId' => $this->post->id];
    }

    public static function getType(): string
    {
        return 'testMailable';
    }

    public static function getSubjectModel(): string
    {
        return \Flarum\Post\Post::class;
    }

    public function getEmailViews(): array
    {
        return [
            'text' => 'flarum.forum::email.plain.notification',
            'html' => 'flarum.forum::email.html.notification',
        ];
    }

    public function getEmailSubject($translator): string
    {
        return 'Test Email Subject';
    }
}
