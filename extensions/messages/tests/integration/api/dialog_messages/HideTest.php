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
 * "Delete" takes a message away in a way that can be undone: the other
 * members see that it was deleted, not what it said.
 */
class HideTest extends TestCase
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
                ['id' => 103, 'dialog_id' => 102, 'user_id' => 3, 'content' => '<t><p>Pineapple secret</p></t>', 'number' => 2, 'created_at' => Carbon::now()],
            ],
            'dialog_user' => [
                ['dialog_id' => 102, 'user_id' => 3, 'joined_at' => Carbon::now()],
                ['dialog_id' => 102, 'user_id' => 4, 'joined_at' => Carbon::now()],
            ],
        ]);
    }

    protected function setHidden(int $id, int $actor, bool $hidden)
    {
        return $this->send($this->request('PATCH', '/api/dialog-messages/'.$id, [
            'authenticatedAs' => $actor,
            'json' => ['data' => ['type' => 'dialog-messages', 'id' => (string) $id, 'attributes' => ['isHidden' => $hidden]]],
        ]));
    }

    protected function show(int $id, int $actor): array
    {
        $response = $this->send($this->request('GET', '/api/dialog-messages/'.$id, ['authenticatedAs' => $actor]));

        $this->assertEquals(200, $response->getStatusCode());

        return json_decode($response->getBody()->getContents(), true)['data']['attributes'];
    }

    #[Test]
    public function by_default_an_author_can_delete_their_latest_message(): void
    {
        $response = $this->setHidden(103, 3, true);

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());

        $row = $this->database()->table('dialog_messages')->where('id', 103)->first();
        $this->assertNotNull($row->hidden_at);
        $this->assertEquals(3, $row->hidden_user_id);
    }

    #[Test]
    public function the_other_members_see_that_it_was_deleted_but_not_what_it_said(): void
    {
        $this->setHidden(103, 3, true);

        $forBob = $this->show(103, 4);
        $this->assertTrue($forBob['isHidden']);
        $this->assertArrayNotHasKey('contentHtml', $forBob);

        $forAlice = $this->show(103, 3);
        $this->assertTrue($forAlice['isHidden']);
        $this->assertStringContainsString('Pineapple secret', $forAlice['contentHtml']);
        $this->assertTrue($forAlice['canHide']);
    }

    #[Test]
    public function an_author_can_restore_what_they_deleted(): void
    {
        $this->setHidden(103, 3, true);

        $response = $this->setHidden(103, 3, false);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($this->database()->table('dialog_messages')->where('id', 103)->value('hidden_at'));
        $this->assertStringContainsString('Pineapple secret', $this->show(103, 4)['contentHtml']);
    }

    #[Test]
    public function an_author_cannot_restore_what_someone_else_deleted(): void
    {
        $this->database()->table('dialog_messages')->where('id', 103)->update(['hidden_at' => Carbon::now(), 'hidden_user_id' => 1]);

        $this->assertEquals(403, $this->setHidden(103, 3, false)->getStatusCode());
    }

    #[Test]
    public function another_members_message_cannot_be_deleted(): void
    {
        $this->setting('flarum-messages.allow_delete_own_messages', '-1');

        $this->assertEquals(403, $this->setHidden(103, 4, true)->getStatusCode());
        $this->assertFalse($this->show(103, 4)['canHide']);
    }

    #[Test]
    public function deleting_can_be_turned_off(): void
    {
        $this->setting('flarum-messages.allow_delete_own_messages', '0');

        $this->assertEquals(403, $this->setHidden(103, 3, true)->getStatusCode());
    }

    #[Test]
    public function alerts_about_a_deleted_message_go_with_it_and_those_still_liked_come_back(): void
    {
        $this->prepareDatabase([
            User::class => [
                ['id' => 5, 'username' => 'carol'],
            ],
            // Bob still likes it; carol liked it and took the like back, so
            // her alert was already withdrawn.
            'dialog_message_likes' => [
                ['dialog_message_id' => 103, 'user_id' => 4],
            ],
            'notifications' => [
                ['id' => 1, 'user_id' => 3, 'from_user_id' => 4, 'type' => 'messageLiked', 'subject_id' => 103, 'created_at' => Carbon::now(), 'is_deleted' => 0],
                ['id' => 2, 'user_id' => 3, 'from_user_id' => 5, 'type' => 'messageLiked', 'subject_id' => 103, 'created_at' => Carbon::now(), 'is_deleted' => 1],
            ],
        ]);

        $deleted = fn (int $id) => (int) $this->database()->table('notifications')->where('id', $id)->value('is_deleted');

        $this->setHidden(103, 3, true);
        $this->assertSame(1, $deleted(1));
        $this->assertSame(1, $deleted(2));

        $this->setHidden(103, 3, false);
        $this->assertSame(0, $deleted(1));
        $this->assertSame(1, $deleted(2));
    }
}
