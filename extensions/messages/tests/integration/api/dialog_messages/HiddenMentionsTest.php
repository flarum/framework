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
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Who a deleted message mentioned is part of what it said: the other members
 * don't get it.
 */
class HiddenMentionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions', 'flarum-messages');

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'alice'],
                ['id' => 4, 'username' => 'bob'],
                ['id' => 5, 'username' => 'carol'],
            ],
            Dialog::class => [
                ['id' => 102, 'type' => 'direct', 'first_message_id' => 102, 'last_message_id' => 102, 'last_message_at' => Carbon::now()],
            ],
            DialogMessage::class => [
                ['id' => 102, 'dialog_id' => 102, 'user_id' => 3, 'content' => '<r><p>Ask <USERMENTION displayname="carol" id="5">@"carol"#5</USERMENTION></p></r>', 'number' => 1, 'created_at' => Carbon::now(), 'hidden_at' => Carbon::now(), 'hidden_user_id' => 3],
            ],
            'dialog_user' => [
                ['dialog_id' => 102, 'user_id' => 3, 'joined_at' => Carbon::now()],
                ['dialog_id' => 102, 'user_id' => 4, 'joined_at' => Carbon::now()],
            ],
            'dialog_message_mentions_user' => [
                ['dialog_message_id' => 102, 'mentions_user_id' => 5],
            ],
        ]);
    }

    protected function show(int $as): array
    {
        $response = $this->send($this->request('GET', '/api/dialog-messages/102', ['authenticatedAs' => $as]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        return json_decode((string) $response->getBody(), true);
    }

    #[Test]
    public function the_other_members_do_not_get_who_a_deleted_message_mentioned(): void
    {
        $body = $this->show(4);

        $this->assertArrayNotHasKey('mentionsUsers', $body['data']['relationships'] ?? []);
        $this->assertNotContains('5', array_column(array_filter($body['included'] ?? [], fn ($item) => $item['type'] === 'users'), 'id'));
    }

    #[Test]
    public function its_author_still_does(): void
    {
        $this->assertSame('5', $this->show(3)['data']['relationships']['mentionsUsers']['data'][0]['id']);
    }
}
