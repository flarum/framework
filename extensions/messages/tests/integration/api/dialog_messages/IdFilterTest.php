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
 * Messages asked for by id, as a conversation already on screen fetches its
 * messages again after a reconnect: only those, and only ones the member may
 * see.
 */
class IdFilterTest extends TestCase
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
                ['id' => 5, 'username' => 'carol'],
            ],
            Dialog::class => [
                ['id' => 201, 'type' => 'direct', 'first_message_id' => 201, 'last_message_id' => 203, 'last_message_at' => Carbon::now()],
            ],
            DialogMessage::class => [
                ['id' => 201, 'dialog_id' => 201, 'user_id' => 4, 'content' => '<t><p>One</p></t>', 'number' => 1],
                ['id' => 202, 'dialog_id' => 201, 'user_id' => 3, 'content' => '<t><p>Two</p></t>', 'number' => 2],
                ['id' => 203, 'dialog_id' => 201, 'user_id' => 4, 'content' => '<t><p>Three</p></t>', 'number' => 3],
            ],
            'dialog_user' => [
                ['dialog_id' => 201, 'user_id' => 3, 'joined_at' => Carbon::now()],
                ['dialog_id' => 201, 'user_id' => 4, 'joined_at' => Carbon::now()],
            ],
        ]);
    }

    protected function ids(int $as, string $filter): array
    {
        $response = $this->send(
            $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => $as])
                ->withQueryParams(['filter' => ['dialog' => '201', 'id' => $filter]])
        );

        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals(200, $response->getStatusCode(), json_encode($body));

        return array_column($body['data'], 'id');
    }

    #[Test]
    public function only_the_messages_asked_for_come_back(): void
    {
        $this->assertEqualsCanonicalizing(['201', '203'], $this->ids(3, '201,203'));
    }

    #[Test]
    public function messages_the_member_cannot_see_do_not(): void
    {
        $this->assertEquals([], $this->ids(5, '201,202,203'));
    }
}
