<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration;

use Carbon\Carbon;
use Flarum\Audit\AuditLog;
use Flarum\Audit\Tests\integration\InteractsWithAuditLog;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * The audit log records who wrote to whom in private conversations, one entry
 * per recipient, and never what was written.
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
                ['id' => 5, 'username' => 'carol', 'email' => 'carol@machine.local', 'is_email_confirmed' => 1],
            ],
            // The admin already has a conversation with alice, and one with
            // alice and carol together.
            Dialog::class => [
                ['id' => 102, 'type' => 'direct'],
                ['id' => 103, 'type' => 'direct'],
            ],
            DialogMessage::class => [
                ['id' => 102, 'dialog_id' => 102, 'user_id' => 3, 'content' => 'Hi', 'number' => 1, 'created_at' => Carbon::now()->subHour()],
                ['id' => 103, 'dialog_id' => 103, 'user_id' => 3, 'content' => 'Hi all', 'number' => 1, 'created_at' => Carbon::now()->subHour()],
            ],
            'dialog_user' => [
                ['dialog_id' => 102, 'user_id' => 1, 'joined_at' => Carbon::now()->subHour()],
                ['dialog_id' => 102, 'user_id' => 3, 'joined_at' => Carbon::now()->subHour()],
                ['dialog_id' => 103, 'user_id' => 1, 'joined_at' => Carbon::now()->subHour()],
                ['dialog_id' => 103, 'user_id' => 3, 'joined_at' => Carbon::now()->subHour()],
                ['dialog_id' => 103, 'user_id' => 5, 'joined_at' => Carbon::now()->subHour()],
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

    private function reply(string $dialogId): void
    {
        $this->message([
            'attributes' => ['content' => 'And again'],
            'relationships' => ['dialog' => ['data' => ['type' => 'dialogs', 'id' => $dialogId]]],
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function payloads(string $action): array
    {
        return AuditLog::query()->where('action', $action)->get()->pluck('payload')->all();
    }

    #[Test]
    public function starting_a_conversation_is_logged_with_who_it_was_started_with(): void
    {
        $body = $this->message(['attributes' => ['content' => 'Hello', 'users' => [['id' => 4]]]]);

        $this->assertLogExists('dialog.started', [
            'dialog_id' => (int) $body['data']['relationships']['dialog']['data']['id'],
            'user_id' => 4,
        ]);
        // The first message is the start, not also a message posted.
        $this->assertLogDoesntExist('dialog.posted');
    }

    #[Test]
    public function a_reply_is_logged_with_who_it_was_sent_to(): void
    {
        $this->reply('102');

        $this->assertLogExists('dialog.posted', ['dialog_id' => 102, 'user_id' => 3]);
        $this->assertLogDoesntExist('dialog.started');
    }

    // Naming someone already in a conversation with the sender adds to that one.
    #[Test]
    public function writing_to_an_existing_contact_again_is_a_message_posted_not_a_new_start(): void
    {
        $this->message(['attributes' => ['content' => 'Hello again', 'users' => [['id' => 3]]]]);

        $this->assertLogExists('dialog.posted', ['dialog_id' => 102, 'user_id' => 3]);
        $this->assertLogDoesntExist('dialog.started');
    }

    #[Test]
    public function a_message_to_several_people_is_logged_once_for_each_of_them_and_not_for_the_sender(): void
    {
        $this->reply('103');

        $this->assertEqualsCanonicalizing([
            ['dialog_id' => 103, 'user_id' => 3],
            ['dialog_id' => 103, 'user_id' => 5],
        ], $this->payloads('dialog.posted'));
    }

    #[Test]
    public function nothing_of_what_was_written_is_logged(): void
    {
        $this->reply('102');

        foreach ($this->payloads('dialog.posted') as $payload) {
            // Key order doesn't survive the JSON column, so only the set is compared.
            $this->assertEqualsCanonicalizing(['dialog_id', 'user_id'], array_keys($payload));
        }
    }
}
