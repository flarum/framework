<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api\dialog_messages;

use Carbon\Carbon;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Without flarum/likes, messages can't be liked and carry nothing about likes.
 */
class LikesDisabledTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages');

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'alice'],
                ['id' => 4, 'username' => 'bob'],
            ],
            Dialog::class => [
                ['id' => 201, 'type' => 'direct', 'first_message_id' => 201, 'last_message_id' => 201, 'last_message_at' => Carbon::now()],
            ],
            DialogMessage::class => [
                ['id' => 201, 'dialog_id' => 201, 'user_id' => 4, 'content' => '<t><p>Hi Alice</p></t>', 'number' => 1],
            ],
            'dialog_user' => [
                ['dialog_id' => 201, 'user_id' => 3, 'joined_at' => Carbon::now()],
                ['dialog_id' => 201, 'user_id' => 4, 'joined_at' => Carbon::now()],
            ],
        ]);
    }

    #[Test]
    public function messages_cannot_be_liked(): void
    {
        $response = $this->send($this->request('POST', '/api/dialog-messages/201/like', ['authenticatedAs' => 3]));

        $this->assertContains($response->getStatusCode(), [404, 405]);
        $this->assertEquals(0, $this->database()->table('dialog_message_likes')->count());
    }

    #[Test]
    public function messages_carry_nothing_about_likes(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => 3])->withQueryParams(['filter' => ['dialog' => '201']])
        );

        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode(), json_encode($body));
        $this->assertArrayNotHasKey('likesCount', $body['data'][0]['attributes']);
        $this->assertArrayNotHasKey('canLike', $body['data'][0]['attributes']);
        $this->assertArrayNotHasKey('likes', $body['data'][0]['relationships'] ?? []);
    }
}
