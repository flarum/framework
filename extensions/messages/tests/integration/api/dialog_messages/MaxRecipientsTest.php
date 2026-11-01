<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api\dialog_messages;

use Flarum\Messages\Dialog;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

/**
 * How many members one message can reach is capped: by default ten, at most
 * fifty, and an admin setting it to one turns group conversations off.
 */
class MaxRecipientsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages');

        $users = [];

        // Members 3 to 63: the sender, and sixty possible recipients. Not
        // emailed: core queues one job per email, and the sync queue runs each
        // inside the request, which isn't what is under test here.
        foreach (range(3, 63) as $id) {
            $users[] = [
                'id' => $id, 'username' => "member$id", 'email' => "member$id@machine.local", 'is_email_confirmed' => 1,
                'preferences' => json_encode(['notify_messageReceived_email' => false]),
            ];
        }

        $this->prepareDatabase([User::class => $users]);
    }

    protected function sendTo(int $count): ResponseInterface
    {
        return $this->send(
            $this->request('POST', '/api/dialog-messages', [
                'authenticatedAs' => 3,
                'json' => [
                    'data' => [
                        'type' => 'dialog-messages',
                        'attributes' => [
                            'content' => 'Hello, everyone!',
                            'users' => array_map(fn (int $id) => ['id' => $id], range(4, 3 + $count)),
                        ],
                    ],
                ],
            ])
        );
    }

    #[Test]
    public function ten_recipients_are_allowed_by_default(): void
    {
        $response = $this->sendTo(10);

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function eleven_recipients_are_too_many_by_default_and_nothing_is_written(): void
    {
        $response = $this->sendTo(11);

        $this->assertEquals(422, $response->getStatusCode(), (string) $response->getBody());
        $error = json_decode((string) $response->getBody(), true)['errors'][0];
        $this->assertEquals('/data/attributes/users', $error['source']['pointer']);
        $this->assertEquals('A message can go to at most 10 members.', $error['detail']);
        $this->assertEquals(0, Dialog::query()->count());
    }

    #[Test]
    public function an_admin_can_raise_the_limit(): void
    {
        $this->setting('flarum-messages.max_recipients', '20');

        $this->assertEquals(201, $this->sendTo(20)->getStatusCode());
    }

    #[Test]
    public function the_limit_cannot_be_raised_past_fifty(): void
    {
        $this->setting('flarum-messages.max_recipients', '500');

        $this->assertEquals(422, $this->sendTo(51)->getStatusCode());
    }

    /**
     * A member can always send one message as large as the forum allows,
     * however few people the hourly allowance would otherwise let them reach.
     */
    #[Test]
    public function fifty_recipients_are_allowed_when_the_limit_is_fifty(): void
    {
        $this->setting('flarum-messages.max_recipients', '50');

        $response = $this->sendTo(50);

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function a_limit_of_one_turns_group_conversations_off_but_leaves_direct_ones(): void
    {
        $this->setting('flarum-messages.max_recipients', '1');

        $this->assertEquals(422, $this->sendTo(2)->getStatusCode());
        $this->assertEquals(201, $this->sendTo(1)->getStatusCode());
    }

    #[Test]
    public function the_forum_is_told_the_limit(): void
    {
        $this->setting('flarum-messages.max_recipients', '500');

        $response = $this->send($this->request('GET', '/api', ['authenticatedAs' => 3]));

        $this->assertSame(50, json_decode((string) $response->getBody(), true)['data']['attributes']['messagesMaxRecipients']);
    }
}
