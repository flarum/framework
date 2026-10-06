<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration;

use Carbon\Carbon;
use Flarum\Audit\Tests\integration\InteractsWithAuditLog;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * The audit log records who started a conversation with whom, and nothing about
 * the messages that follow.
 */
class AuditTest extends TestCase
{
    use InteractsWithAuditLog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAuditLog();

        $this->extension('flarum-audit', 'flarum-messages');

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'alice', 'email' => 'alice@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'bob', 'email' => 'bob@machine.local', 'is_email_confirmed' => 1],
            ],
            // The admin already has a conversation with alice.
            Dialog::class => [
                ['id' => 102, 'type' => 'direct'],
            ],
            DialogMessage::class => [
                ['id' => 102, 'dialog_id' => 102, 'user_id' => 3, 'content' => 'Hi', 'number' => 1, 'created_at' => Carbon::now()->subHour()],
            ],
            'dialog_user' => [
                ['dialog_id' => 102, 'user_id' => 1, 'joined_at' => Carbon::now()->subHour()],
                ['dialog_id' => 102, 'user_id' => 3, 'joined_at' => Carbon::now()->subHour()],
            ],
        ]);
    }

    private function message(array $data): array
    {
        $response = $this->sendSuccessfulRequest('POST', '/api/dialog-messages', [
            'json' => ['data' => ['type' => 'dialog-messages'] + $data],
        ], 201);

        return json_decode($response->getBody()->getContents(), true);
    }

    #[Test]
    public function starting_a_conversation_is_logged_with_who_it_was_started_with(): void
    {
        $body = $this->message(['attributes' => ['content' => 'Hello', 'users' => [['id' => 4]]]]);

        $this->assertLogExists('dialog.started', [
            'dialog_id' => (int) $body['data']['relationships']['dialog']['data']['id'],
            'user_id' => 4,
        ]);
    }

    #[Test]
    public function a_reply_is_not_logged(): void
    {
        $this->message([
            'attributes' => ['content' => 'And again'],
            'relationships' => ['dialog' => ['data' => ['type' => 'dialogs', 'id' => '102']]],
        ]);

        $this->assertLogDoesntExist('dialog.started');
    }

    // Naming someone already in a conversation with the sender adds to that one.
    #[Test]
    public function writing_to_an_existing_contact_again_is_not_logged(): void
    {
        $this->message(['attributes' => ['content' => 'Hello again', 'users' => [['id' => 3]]]]);

        $this->assertLogDoesntExist('dialog.started');
    }
}
