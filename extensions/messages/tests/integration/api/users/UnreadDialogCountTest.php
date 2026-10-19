<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api\users;

use Carbon\Carbon;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * The badge on the header's messages icon: how many of a member's
 * conversations have something unread. Conversations, not messages, and only
 * ever shown to the member themselves.
 */
class UnreadDialogCountTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages');

        $now = Carbon::now();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'alice'],
                ['id' => 4, 'username' => 'bob'],
                ['id' => 5, 'username' => 'carol'],
            ],
            Dialog::class => [
                // Three unread messages from alice.
                ['id' => 101, 'type' => 'direct', 'first_message_id' => 101, 'last_message_id' => 103, 'last_message_at' => $now],
                // Read up to its last message.
                ['id' => 102, 'type' => 'direct', 'first_message_id' => 104, 'last_message_id' => 104, 'last_message_at' => $now],
                // One unread message from carol.
                ['id' => 103, 'type' => 'direct', 'first_message_id' => 105, 'last_message_id' => 105, 'last_message_at' => $now],
            ],
            DialogMessage::class => [
                ['id' => 101, 'dialog_id' => 101, 'user_id' => 3, 'content' => 'One', 'number' => 1, 'created_at' => $now],
                ['id' => 102, 'dialog_id' => 101, 'user_id' => 3, 'content' => 'Two', 'number' => 2, 'created_at' => $now],
                ['id' => 103, 'dialog_id' => 101, 'user_id' => 3, 'content' => 'Three', 'number' => 3, 'created_at' => $now],
                ['id' => 104, 'dialog_id' => 102, 'user_id' => 4, 'content' => 'Hi', 'number' => 1, 'created_at' => $now],
                ['id' => 105, 'dialog_id' => 103, 'user_id' => 5, 'content' => 'Hey', 'number' => 1, 'created_at' => $now],
            ],
            'dialog_user' => [
                ['dialog_id' => 101, 'user_id' => 2, 'joined_at' => $now, 'last_read_message_id' => 0],
                ['dialog_id' => 101, 'user_id' => 3, 'joined_at' => $now, 'last_read_message_id' => 103],
                ['dialog_id' => 102, 'user_id' => 2, 'joined_at' => $now, 'last_read_message_id' => 104],
                ['dialog_id' => 102, 'user_id' => 4, 'joined_at' => $now, 'last_read_message_id' => 104],
                ['dialog_id' => 103, 'user_id' => 2, 'joined_at' => $now, 'last_read_message_id' => 0],
                ['dialog_id' => 103, 'user_id' => 5, 'joined_at' => $now, 'last_read_message_id' => 105],
            ],
        ]);
    }

    private function attributes(?int $as, int $user): array
    {
        $response = $this->send($this->request('GET', "/api/users/$user", $as ? ['authenticatedAs' => $as] : []));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        return json_decode((string) $response->getBody(), true)['data']['attributes'];
    }

    #[Test]
    public function it_counts_conversations_with_something_unread_not_messages(): void
    {
        // Two unread conversations, holding four unread messages between them.
        $this->assertSame(2, $this->attributes(2, 2)['unreadDialogCount']);
    }

    #[Test]
    public function nobody_else_is_shown_it(): void
    {
        $this->assertArrayNotHasKey('unreadDialogCount', $this->attributes(3, 2));
        $this->assertArrayNotHasKey('unreadDialogCount', $this->attributes(null, 2));
    }

    // Renamed before 2.0 was stable, for what it counts.
    #[Test]
    public function the_old_name_is_gone(): void
    {
        $this->assertArrayNotHasKey('messageCount', $this->attributes(2, 2));
    }
}
