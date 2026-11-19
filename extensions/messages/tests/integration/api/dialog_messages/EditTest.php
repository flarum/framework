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
 * Members edit their own messages, for as long as the forum allows: by
 * default until someone replies.
 */
class EditTest extends TestCase
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
                ['id' => 102, 'type' => 'direct', 'first_message_id' => 102, 'last_message_id' => 103, 'last_message_at' => Carbon::now()],
            ],
            DialogMessage::class => [
                ['id' => 102, 'dialog_id' => 102, 'user_id' => 4, 'content' => '<t><p>Hi Alice</p></t>', 'number' => 1, 'created_at' => Carbon::now()],
                ['id' => 103, 'dialog_id' => 102, 'user_id' => 3, 'content' => '<t><p>Hi Bob</p></t>', 'number' => 2, 'created_at' => Carbon::now()],
            ],
            'dialog_user' => [
                ['dialog_id' => 102, 'user_id' => 3, 'joined_at' => Carbon::now()],
                ['dialog_id' => 102, 'user_id' => 4, 'joined_at' => Carbon::now()],
            ],
        ]);
    }

    protected function edit(int $id, int $actor, string $content = 'Hello Bob')
    {
        return $this->send($this->request('PATCH', '/api/dialog-messages/'.$id, [
            'authenticatedAs' => $actor,
            'json' => ['data' => ['type' => 'dialog-messages', 'id' => (string) $id, 'attributes' => ['content' => $content]]],
        ]));
    }

    protected function show(int $id, int $actor): array
    {
        $response = $this->send($this->request('GET', '/api/dialog-messages/'.$id, ['authenticatedAs' => $actor]));

        $this->assertEquals(200, $response->getStatusCode());

        return json_decode($response->getBody()->getContents(), true)['data']['attributes'];
    }

    #[Test]
    public function by_default_the_latest_message_can_be_edited_by_its_author(): void
    {
        $response = $this->edit(103, 3);
        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $attributes = json_decode($body, true)['data']['attributes'];
        $this->assertStringContainsString('Hello Bob', $attributes['contentHtml']);
        $this->assertNotNull($attributes['editedAt']);

        $row = $this->database()->table('dialog_messages')->where('id', 103)->first();
        $this->assertEquals(3, $row->edited_user_id);
    }

    #[Test]
    public function by_default_a_message_that_has_been_replied_to_cannot_be_edited(): void
    {
        $this->assertEquals(403, $this->edit(102, 4)->getStatusCode());
    }

    #[Test]
    public function another_members_message_cannot_be_edited(): void
    {
        $this->setting('flarum-messages.allow_edit_own_messages', '-1');

        $this->assertEquals(403, $this->edit(103, 4)->getStatusCode());
        $this->assertFalse($this->show(103, 4)['canEdit']);
    }

    #[Test]
    public function editing_can_be_allowed_indefinitely(): void
    {
        $this->setting('flarum-messages.allow_edit_own_messages', '-1');

        $this->assertEquals(200, $this->edit(102, 4, 'Hello Alice')->getStatusCode());
    }

    #[Test]
    public function editing_can_be_turned_off(): void
    {
        $this->setting('flarum-messages.allow_edit_own_messages', '0');

        $this->assertEquals(403, $this->edit(103, 3)->getStatusCode());
        $this->assertFalse($this->show(103, 3)['canEdit']);
    }

    #[Test]
    public function editing_for_some_minutes_ends_after_them(): void
    {
        $this->setting('flarum-messages.allow_edit_own_messages', '10');

        $this->database()->table('dialog_messages')->where('id', 103)->update(['created_at' => Carbon::now()->subMinutes(11)]);

        $this->assertEquals(403, $this->edit(103, 3)->getStatusCode());
    }

    #[Test]
    public function the_same_content_is_not_an_edit(): void
    {
        $response = $this->edit(103, 3, 'Hi Bob');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull(json_decode($response->getBody()->getContents(), true)['data']['attributes']['editedAt']);
    }

    #[Test]
    public function only_who_can_edit_a_message_gets_its_source(): void
    {
        $this->assertSame('Hi Bob', $this->show(103, 3)['content']);
        $this->assertTrue($this->show(103, 3)['canEdit']);
        $this->assertArrayNotHasKey('content', $this->show(103, 4));
    }

    #[Test]
    public function a_notice_cannot_be_edited(): void
    {
        $this->setting('flarum-messages.allow_edit_own_messages', '-1');

        $this->prepareDatabase([
            DialogMessage::class => [
                ['id' => 104, 'dialog_id' => 102, 'user_id' => 3, 'type' => 'renamed', 'content' => '{"name":"Hi"}', 'number' => 3],
            ],
        ]);

        $this->assertEquals(403, $this->edit(104, 3)->getStatusCode());
    }

    #[Test]
    public function a_deleted_message_cannot_be_edited(): void
    {
        $this->database()->table('dialog_messages')->where('id', 103)->update(['hidden_at' => Carbon::now(), 'hidden_user_id' => 3]);

        $this->assertEquals(403, $this->edit(103, 3)->getStatusCode());
    }

    #[Test]
    public function a_member_who_may_no_longer_send_cannot_edit(): void
    {
        $this->database()->table('group_permission')->where('permission', 'dialog.sendMessage')->delete();

        $this->assertEquals(403, $this->edit(103, 3)->getStatusCode());
    }

    #[Test]
    public function a_message_cannot_be_emptied(): void
    {
        foreach (['', '   '] as $content) {
            $this->assertEquals(422, $this->edit(103, 3, $content)->getStatusCode(), json_encode($content));
        }
    }

    /**
     * Whether each conversation's latest message can be edited or deleted is
     * worked out for the list too: once for the page, not per conversation.
     */
    #[Test]
    public function listing_conversations_whose_latest_message_is_ones_own_does_not_query_per_conversation(): void
    {
        $this->prepareDatabase([
            User::class => [
                ['id' => 5, 'username' => 'carol'],
                ['id' => 6, 'username' => 'dave'],
            ],
            Dialog::class => [
                ['id' => 103, 'type' => 'direct', 'first_message_id' => 104, 'last_message_id' => 104, 'last_message_at' => Carbon::now()],
                ['id' => 104, 'type' => 'direct', 'first_message_id' => 105, 'last_message_id' => 105, 'last_message_at' => Carbon::now()],
            ],
            DialogMessage::class => [
                ['id' => 104, 'dialog_id' => 103, 'user_id' => 3, 'content' => '<t><p>Hi Carol</p></t>', 'number' => 1, 'created_at' => Carbon::now()],
                ['id' => 105, 'dialog_id' => 104, 'user_id' => 3, 'content' => '<t><p>Hi Dave</p></t>', 'number' => 1, 'created_at' => Carbon::now()],
            ],
            'dialog_user' => [
                ['dialog_id' => 103, 'user_id' => 3, 'joined_at' => Carbon::now()],
                ['dialog_id' => 103, 'user_id' => 5, 'joined_at' => Carbon::now()],
                ['dialog_id' => 104, 'user_id' => 3, 'joined_at' => Carbon::now()],
                ['dialog_id' => 104, 'user_id' => 6, 'joined_at' => Carbon::now()],
            ],
        ]);

        $response = $this->send(
            $this->request('GET', '/api/dialogs', ['authenticatedAs' => 3])
                ->withQueryParams(['include' => 'users,users.groups,lastMessage,lastMessage.user'])
        );

        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $body);

        $included = array_values(array_filter(json_decode($body, true)['included'], fn ($item) => $item['type'] === 'dialog-messages'));
        $this->assertCount(3, $included);
        $this->assertTrue($included[0]['attributes']['canEdit']);

        // And the messages of several conversations at once, as search lists them.
        $response = $this->send(
            $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => 3])
                ->withQueryParams(['filter' => ['dialog' => ['102', '103', '104']]])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function the_conversation_says_who_edited_a_message(): void
    {
        $this->edit(103, 3);

        $response = $this->send(
            $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => 4])
                ->withQueryParams(['filter' => ['dialog' => '102']])
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $message = array_values(array_filter($body['data'], fn ($item) => $item['id'] === '103'))[0];

        $this->assertSame('3', $message['relationships']['editedUser']['data']['id']);
        $this->assertContains('3', array_column(array_filter($body['included'], fn ($item) => $item['type'] === 'users'), 'id'));
    }

    /**
     * Each message's own edit and delete rights, and who edited or deleted
     * it, for a whole page: not a query per message.
     */
    #[Test]
    public function listing_many_of_ones_own_messages_edited_and_deleted_does_not_query_per_message(): void
    {
        $this->setting('flarum-messages.allow_edit_own_messages', '-1');
        $this->setting('flarum-messages.allow_delete_own_messages', '-1');

        $messages = [];
        for ($i = 0; $i < 8; $i++) {
            $messages[] = [
                'id' => 110 + $i, 'dialog_id' => 102, 'user_id' => 3, 'content' => '<t><p>Message '.$i.'</p></t>', 'number' => 10 + $i, 'created_at' => Carbon::now(),
                'edited_at' => $i % 2 ? Carbon::now() : null, 'edited_user_id' => $i % 2 ? 3 : null,
                'hidden_at' => $i % 3 === 0 ? Carbon::now() : null, 'hidden_user_id' => $i % 3 === 0 ? 3 : null,
            ];
        }
        $this->prepareDatabase([DialogMessage::class => $messages]);

        foreach ([3, 4] as $as) {
            $response = $this->send(
                $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => $as])
                    ->withQueryParams(['filter' => ['dialog' => '102']])
            );

            $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        }
    }
}
