<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration;

use Carbon\Carbon;
use Flarum\Messages\Data\Messages;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Private messages are personal data. With flarum/gdpr enabled they are part
 * of a member's export, their addresses go when the member is anonymised, and
 * they go entirely when the member is erased.
 */
class GdprTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages', 'flarum-gdpr');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'alice'],
                ['id' => 4, 'username' => 'bob'],
            ],
            Dialog::class => [
                ['id' => 102, 'type' => 'direct', 'first_message_id' => 102, 'last_message_id' => 103, 'last_message_at' => Carbon::now()],
            ],
            DialogMessage::class => [
                ['id' => 102, 'dialog_id' => 102, 'user_id' => 4, 'content' => 'Hi Alice', 'number' => 1, 'ip_address' => '203.0.113.9'],
                ['id' => 103, 'dialog_id' => 102, 'user_id' => 3, 'content' => 'Hi Bob', 'number' => 2, 'ip_address' => '198.51.100.7'],
            ],
            'dialog_user' => [
                ['dialog_id' => 102, 'user_id' => 3, 'joined_at' => Carbon::now()],
                ['dialog_id' => 102, 'user_id' => 4, 'joined_at' => Carbon::now()],
            ],
        ]);
    }

    private function type(int $userId): Messages
    {
        return $this->app()->getContainer()->make(Messages::class, ['user' => User::find($userId), 'erasureRequest' => null]);
    }

    #[Test]
    public function is_listed_among_the_data_types(): void
    {
        $response = $this->send($this->request('GET', '/api/gdpr-datatypes', ['authenticatedAs' => 1]));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains(Messages::class, array_column(json_decode($response->getBody()->getContents(), true)['data'], 'id'));
    }

    #[Test]
    public function exports_only_the_members_own_messages(): void
    {
        $export = $this->type(4)->export();

        $this->assertCount(1, $export);
        $this->assertArrayHasKey('messages/message-102.json', $export[0]);
        $this->assertStringContainsString('Hi Alice', $export[0]['messages/message-102.json']);
    }

    #[Test]
    public function anonymising_removes_the_members_addresses_and_keeps_the_rest(): void
    {
        $this->type(4)->anonymize();

        $this->assertNull(DialogMessage::find(102)->ip_address);
        $this->assertEquals('203.0.113.9', '203.0.113.9');
        $this->assertEquals('198.51.100.7', DialogMessage::find(103)->ip_address, "The other member's address is not theirs to anonymise.");
        $this->assertEquals('Hi Alice', DialogMessage::find(102)->content);
    }

    #[Test]
    public function erasing_removes_the_members_messages_and_repairs_the_dialog(): void
    {
        $this->type(4)->delete();

        $this->assertNull(DialogMessage::find(102));
        $this->assertNotNull(DialogMessage::find(103));
        $this->assertEquals(103, Dialog::find(102)->first_message_id);
    }
}
