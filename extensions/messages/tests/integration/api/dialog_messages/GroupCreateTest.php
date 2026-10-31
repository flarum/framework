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
use Psr\Http\Message\ResponseInterface;

/**
 * A message to more than one recipient starts a group conversation: a new one
 * every time, with the sender and every recipient as members.
 */
class GroupCreateTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages');

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'alice', 'email' => 'alice@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'bob', 'email' => 'bob@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'carol', 'email' => 'carol@machine.local', 'is_email_confirmed' => 1],
                ['id' => 6, 'username' => 'dave', 'email' => 'dave@machine.local', 'is_email_confirmed' => 1],
            ],
        ]);
    }

    protected function sendTo(array $recipients, int $actor = 3): ResponseInterface
    {
        return $this->send(
            $this->request('POST', '/api/dialog-messages', [
                'authenticatedAs' => $actor,
                'json' => [
                    'data' => [
                        'type' => 'dialog-messages',
                        'attributes' => [
                            'content' => 'Hello, everyone!',
                            'users' => array_map(fn (int $id) => ['id' => $id], $recipients),
                        ],
                    ],
                ],
            ])->withQueryParams(['include' => 'dialog.users'])
        );
    }

    #[Test]
    public function a_message_to_two_recipients_starts_a_group_conversation_with_all_three(): void
    {
        $response = $this->sendTo([4, 5]);

        $body = (string) $response->getBody();
        $this->assertEquals(201, $response->getStatusCode(), $body);

        $data = json_decode($body, true);
        $dialog = collect($data['included'])->firstWhere('type', 'dialogs');

        $this->assertEquals('group', $dialog['attributes']['type']);
        $this->assertEqualsCanonicalizing(['3', '4', '5'], collect($dialog['relationships']['users']['data'])->pluck('id')->all());
    }
}
