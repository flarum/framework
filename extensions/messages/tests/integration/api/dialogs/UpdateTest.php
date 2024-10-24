<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api\dialogs;

use Carbon\Carbon;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Messages\UserDialogState;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class UpdateTest extends TestCase
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
                ['id' => 5, 'username' => 'karlach'],
            ],
            Dialog::class => [
                ['id' => 102, 'type' => 'direct', 'last_message_id' => 111],
            ],
            DialogMessage::class => [
                ['id' => 102, 'dialog_id' => 102, 'user_id' => 4, 'content' => '<p>Hello, Alice!</p>'],
                ['id' => 103, 'dialog_id' => 102, 'user_id' => 3, 'content' => '<p>Hello, Bob!</p>'],
                ['id' => 104, 'dialog_id' => 102, 'user_id' => 4, 'content' => '<p>Hello, Alice!</p>'],
                ['id' => 105, 'dialog_id' => 102, 'user_id' => 3, 'content' => '<p>Hello, Bob!</p>'],
                ['id' => 106, 'dialog_id' => 102, 'user_id' => 4, 'content' => '<p>Hello, Alice!</p>'],
                ['id' => 107, 'dialog_id' => 102, 'user_id' => 3, 'content' => '<p>Hello, Bob!</p>'],
                ['id' => 108, 'dialog_id' => 102, 'user_id' => 4, 'content' => '<p>Hello, Alice!</p>'],
                ['id' => 109, 'dialog_id' => 102, 'user_id' => 3, 'content' => '<p>Hello, Bob!</p>'],
                ['id' => 110, 'dialog_id' => 102, 'user_id' => 4, 'content' => '<p>Hello, Alice!</p>'],
                ['id' => 111, 'dialog_id' => 102, 'user_id' => 3, 'content' => '<p>Hello, Bob!</p>'],
            ],
            'dialog_user' => [
                ['dialog_id' => 102, 'user_id' => 3, 'last_read_message_id' => 0, 'last_read_at' => null, 'joined_at' => Carbon::now()],
                ['dialog_id' => 102, 'user_id' => 4, 'last_read_message_id' => 0, 'last_read_at' => null, 'joined_at' => Carbon::now()],
            ],
        ]);
    }

    public function test_can_mark_dialog_as_read(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/dialogs/102', [
                'authenticatedAs' => 3,
                'json' => [
                    'data' => [
                        'type' => 'dialogs',
                        'id' => '102',
                        'attributes' => [
                            'lastReadMessageId' => 107,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $state = UserDialogState::query()
            ->where('dialog_id', 102)
            ->where('user_id', 3)
            ->first();

        $this->assertEquals(107, $state->last_read_message_id);
        $this->assertNotNull($state->last_read_at);
    }

    public function test_can_mark_all_as_read(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/dialogs/read', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(204, $response->getStatusCode(), json_encode(json_decode($response->getBody()->getContents()), JSON_PRETTY_PRINT));

        $state = UserDialogState::query()
            ->where('dialog_id', 102)
            ->where('user_id', 3)
            ->first();

        $nonState = UserDialogState::query()
            ->where('dialog_id', 102)
            ->where('user_id', '!=', 3)
            ->first();

        $this->assertNotNull($state->last_read_at);
        $this->assertNull($nonState->last_read_at);

        $this->assertEquals(111, $state->last_read_message_id);
        $this->assertEquals(0, $nonState->last_read_message_id);
    }
}
