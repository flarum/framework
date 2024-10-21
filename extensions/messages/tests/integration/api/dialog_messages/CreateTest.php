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

class CreateTest extends TestCase
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
                ['id' => 102, 'type' => 'direct'],
            ],
            DialogMessage::class => [
                ['id' => 102, 'dialog_id' => 102, 'user_id' => 4, 'content' => 'Hello, Karlach!'],
            ],
            'dialog_user' => [
                ['dialog_id' => 102, 'user_id' => 4, 'joined_at' => Carbon::now()],
                ['dialog_id' => 102, 'user_id' => 5, 'joined_at' => Carbon::now()],
            ],
        ]);
    }

    public function test_can_create_a_direct_private_conversation_with_someone(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/dialog-messages', [
                'authenticatedAs' => 3,
                'json' => [
                    'data' => [
                        'type' => 'dialog-messages',
                        'attributes' => [
                            'content' => 'Hello, Bob!',
                            'users' => [
                                ['id' => 4],
                            ],
                        ],
                    ],
                ],
            ])->withQueryParams(['include' => 'dialog.users,user'])
        );

        $json = $response->getBody()->getContents();
        $data = json_decode($json, true);
        $pretty = json_encode($data, JSON_PRETTY_PRINT);

        $this->assertEquals(201, $response->getStatusCode(), $pretty);
        $this->assertNotEquals(102, $data['data']['relationships']['dialog']['data']['id'], $pretty);
        $this->assertEquals('direct', collect($data['included'])->firstWhere('type', 'dialogs')['attributes']['type'], $pretty);
        $this->assertEquals('Hello, Bob!', $data['data']['attributes']['contentHtml'], $pretty);
        $this->assertEqualsCanonicalizing([3, 4], collect(collect($data['included'])->firstWhere('type', 'dialogs')['relationships']['users']['data'])->pluck('id')->all(), $pretty);
    }

    public function test_can_create_a_private_message_when_conversation_already_exists(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/dialog-messages', [
                'authenticatedAs' => 5,
                'json' => [
                    'data' => [
                        'type' => 'dialog-messages',
                        'attributes' => [
                            'content' => 'Hello, Bob!',
                        ],
                        'relationships' => [
                            'dialog' => [
                                'data' => [
                                    'type' => 'dialogs',
                                    'id' => '102',
                                ],
                            ],
                        ],
                    ],
                ],
            ])->withQueryParams(['include' => 'dialog.users,user'])
        );

        $json = $response->getBody()->getContents();
        $data = json_decode($json, true);
        $pretty = json_encode($data, JSON_PRETTY_PRINT);

        $this->assertEquals(201, $response->getStatusCode(), $pretty);
        $this->assertEquals(102, $data['data']['relationships']['dialog']['data']['id'], $pretty);
        $this->assertEquals('direct', collect($data['included'])->firstWhere('type', 'dialogs')['attributes']['type'], $pretty);
        $this->assertEquals('Hello, Bob!', $data['data']['attributes']['contentHtml'], $pretty);
        $this->assertEqualsCanonicalizing([4, 5], collect(collect($data['included'])->firstWhere('type', 'dialogs')['relationships']['users']['data'])->pluck('id')->all(), $pretty);
    }
}
