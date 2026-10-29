<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api\dialog_messages;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Test;

/**
 * A message records the address it was sent from, as a post does, so abuse can
 * be investigated. It is shown only to those allowed to see it.
 */
class IpAddressTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'alice'],
                ['id' => 4, 'username' => 'bob'],
            ],
            Dialog::class => [
                ['id' => 102, 'type' => 'direct'],
            ],
            DialogMessage::class => [
                ['id' => 102, 'dialog_id' => 102, 'user_id' => 4, 'content' => 'Hi', 'number' => 1, 'ip_address' => '203.0.113.9', 'created_at' => Carbon::now()->subHour()],
            ],
            'dialog_user' => [
                ['dialog_id' => 102, 'user_id' => 3, 'joined_at' => Carbon::now()],
                ['dialog_id' => 102, 'user_id' => 4, 'joined_at' => Carbon::now()],
            ],
        ]);
    }

    #[Test]
    public function a_new_message_records_the_senders_address(): void
    {
        // The address comes from the connection, as core's ProcessIp reads it.
        $request = new ServerRequest(['REMOTE_ADDR' => '198.51.100.7'], [], '/api/dialog-messages', 'POST');
        $request = $this->requestWithJsonBody($request, ['data' => [
            'type' => 'dialog-messages',
            'attributes' => ['content' => 'Hello'],
            'relationships' => ['dialog' => ['data' => ['type' => 'dialogs', 'id' => '102']]],
        ]]);
        $request = $this->requestAsUser($request, 3);

        $response = $this->send($request);

        $this->assertEquals(201, $response->getStatusCode());

        $id = json_decode($response->getBody()->getContents(), true)['data']['id'];

        $this->assertEquals('198.51.100.7', DialogMessage::find($id)->ip_address);
    }

    #[Test]
    public function participants_do_not_see_each_others_addresses(): void
    {
        $response = $this->send($this->request('GET', '/api/dialog-messages/102', ['authenticatedAs' => 3]));
        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $this->assertArrayNotHasKey('ipAddress', json_decode($body, true)['data']['attributes']);
    }

    #[Test]
    public function those_allowed_to_view_addresses_see_them(): void
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'dialog.viewIps', 'group_id' => Group::MEMBER_ID],
            ],
        ]);

        $response = $this->send($this->request('GET', '/api/dialog-messages/102', ['authenticatedAs' => 3]));
        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $this->assertEquals('203.0.113.9', json_decode($body, true)['data']['attributes']['ipAddress']);
    }
}
