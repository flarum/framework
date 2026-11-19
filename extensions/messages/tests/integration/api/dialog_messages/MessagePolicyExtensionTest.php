<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api\dialog_messages;

use Carbon\Carbon;
use Flarum\Extend;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Editing and deleting are decided by the message policy, which refuses
 * nobody outright: another extension's policy can allow them too.
 */
class MessagePolicyExtensionTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /** @var DialogMessage\Event\Deleted[] */
    public static array $deleted = [];

    protected function setUp(): void
    {
        parent::setUp();

        self::$deleted = [];

        $this->extension('flarum-messages');

        $this->extend(
            (new Extend\Policy())->modelPolicy(DialogMessage::class, BobMayDoAnything::class),
            (new Extend\Event())->listen(DialogMessage\Event\Deleted::class, fn (DialogMessage\Event\Deleted $event) => self::$deleted[] = $event)
        );

        // Nothing for members' own messages: what bob may do is the other policy's.
        $this->setting('flarum-messages.allow_edit_own_messages', '0');
        $this->setting('flarum-messages.allow_delete_own_messages', '0');

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'alice'],
                ['id' => 4, 'username' => 'bob'],
            ],
            Dialog::class => [
                ['id' => 102, 'type' => 'direct', 'first_message_id' => 102, 'last_message_id' => 103, 'last_message_at' => Carbon::now()],
                ['id' => 103, 'type' => 'direct', 'first_message_id' => 104, 'last_message_id' => 104, 'last_message_at' => Carbon::now()],
            ],
            DialogMessage::class => [
                ['id' => 102, 'dialog_id' => 102, 'user_id' => 3, 'content' => '<t><p>One</p></t>', 'number' => 1, 'created_at' => Carbon::now()],
                ['id' => 103, 'dialog_id' => 102, 'user_id' => 3, 'content' => '<t><p>Two</p></t>', 'number' => 2, 'created_at' => Carbon::now()],
                ['id' => 104, 'dialog_id' => 103, 'user_id' => 3, 'content' => '<t><p>Only</p></t>', 'number' => 1, 'created_at' => Carbon::now()],
            ],
            'dialog_user' => [
                ['dialog_id' => 102, 'user_id' => 3, 'joined_at' => Carbon::now()],
                ['dialog_id' => 102, 'user_id' => 4, 'joined_at' => Carbon::now()],
                ['dialog_id' => 103, 'user_id' => 3, 'joined_at' => Carbon::now()],
                ['dialog_id' => 103, 'user_id' => 4, 'joined_at' => Carbon::now()],
            ],
        ]);
    }

    protected function patch(int $id, array $attributes)
    {
        return $this->send($this->request('PATCH', '/api/dialog-messages/'.$id, [
            'authenticatedAs' => 4,
            'json' => ['data' => ['type' => 'dialog-messages', 'id' => (string) $id, 'attributes' => $attributes]],
        ]));
    }

    #[Test]
    public function another_policy_can_allow_editing_someone_elses_message(): void
    {
        $response = $this->patch(102, ['content' => 'Edited by Bob']);

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());
        $this->assertEquals(4, $this->database()->table('dialog_messages')->where('id', 102)->value('edited_user_id'));
    }

    #[Test]
    public function another_policy_can_allow_deleting_and_restoring_someone_elses_message(): void
    {
        $this->assertEquals(200, $this->patch(102, ['isHidden' => true])->getStatusCode());
        $this->assertEquals(4, $this->database()->table('dialog_messages')->where('id', 102)->value('hidden_user_id'));

        $this->assertEquals(200, $this->patch(102, ['isHidden' => false])->getStatusCode());
        $this->assertNull($this->database()->table('dialog_messages')->where('id', 102)->value('hidden_at'));
    }

    #[Test]
    public function a_notice_cannot_be_deleted_even_by_those_another_policy_allows(): void
    {
        $this->prepareDatabase([
            DialogMessage::class => [
                ['id' => 105, 'dialog_id' => 102, 'user_id' => 3, 'type' => 'renamed', 'content' => '{"name":"Hi"}', 'number' => 3],
            ],
        ]);

        $this->assertEquals(403, $this->patch(105, ['isHidden' => true])->getStatusCode());
    }

    #[Test]
    public function another_policy_can_allow_deleting_someone_elses_message_for_good(): void
    {
        $response = $this->send($this->request('DELETE', '/api/dialog-messages/102', ['authenticatedAs' => 4]));

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertNull($this->database()->table('dialog_messages')->where('id', 102)->first());

        $this->assertCount(1, self::$deleted);
        $this->assertSame(102, self::$deleted[0]->dialogId);
        $this->assertFalse(self::$deleted[0]->dialogDeleted);
        $this->assertEqualsCanonicalizing([3, 4], self::$deleted[0]->memberIds);
    }

    /**
     * Deleting a conversation's last message deletes the conversation, and
     * its memberships with it: who was in it is known from before.
     */
    #[Test]
    public function deleting_the_last_message_says_who_was_in_the_conversation_it_took(): void
    {
        $this->assertEquals(204, $this->send($this->request('DELETE', '/api/dialog-messages/104', ['authenticatedAs' => 4]))->getStatusCode());

        $this->assertNull($this->database()->table('dialogs')->where('id', 103)->first());
        $this->assertCount(1, self::$deleted);
        $this->assertTrue(self::$deleted[0]->dialogDeleted);
        $this->assertEqualsCanonicalizing([3, 4], self::$deleted[0]->memberIds);
    }
}

class BobMayDoAnything extends AbstractPolicy
{
    public function edit(User $actor, DialogMessage $message): ?string
    {
        return $actor->id === 4 ? $this->allow() : null;
    }

    public function hide(User $actor, DialogMessage $message): ?string
    {
        return $actor->id === 4 ? $this->allow() : null;
    }

    public function delete(User $actor, DialogMessage $message): ?string
    {
        return $actor->id === 4 ? $this->allow() : null;
    }
}
