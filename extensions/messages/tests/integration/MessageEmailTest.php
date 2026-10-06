<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration;

use Carbon\Carbon;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Messages\Job\SendMessageNotificationsJob;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

/**
 * A member is emailed about a conversation once, then not again until they
 * have read it: a quick back-and-forth would otherwise send an email for
 * every message.
 */
class MessageEmailTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected \ArrayObject $sent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages');

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'alice', 'email' => 'alice@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'bob', 'email' => 'bob@machine.local', 'is_email_confirmed' => 1],
            ],
            // Several messages in a row, without waiting out the flood control.
            'group_permission' => [
                ['permission' => 'dialog.sendMessageWithoutThrottle', 'group_id' => 3],
            ],
        ]);
    }

    protected function captureSentMail(): void
    {
        $this->sent = $sent = new \ArrayObject();

        $transport = new class($sent) implements TransportInterface {
            public function __construct(private \ArrayObject $sent)
            {
            }

            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                $this->sent[] = $message;

                return new SentMessage($message, $envelope ?? Envelope::create($message));
            }

            public function __toString(): string
            {
                return 'capture';
            }
        };

        $container = $this->app()->getContainer();
        $container->instance('symfony.mailer.transport', $transport);
        $container->forgetInstance('mailer');
    }

    protected function emailsTo(string $address): int
    {
        $count = 0;

        foreach ($this->sent as $message) {
            if ($message instanceof Email && in_array($address, array_map(fn ($to) => $to->getAddress(), $message->getTo()), true)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return array{int, int} The conversation's id and the message's id.
     */
    protected function sendMessage(int $from, string $content, ?int $dialogId = null, ?int $to = null): array
    {
        $data = ['type' => 'dialog-messages', 'attributes' => ['content' => $content]];

        if ($dialogId) {
            $data['relationships'] = ['dialog' => ['data' => ['type' => 'dialogs', 'id' => (string) $dialogId]]];
        } else {
            $data['attributes']['users'] = [['id' => $to]];
        }

        $response = $this->send($this->request('POST', '/api/dialog-messages', ['authenticatedAs' => $from, 'json' => ['data' => $data]]));

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        $body = json_decode((string) $response->getBody(), true);

        return [(int) $body['data']['relationships']['dialog']['data']['id'], (int) $body['data']['id']];
    }

    protected function readUpTo(int $userId, int $dialogId, int $messageId): void
    {
        $response = $this->send($this->request('PATCH', "/api/dialogs/$dialogId", [
            'authenticatedAs' => $userId,
            'json' => ['data' => ['type' => 'dialogs', 'id' => (string) $dialogId, 'attributes' => ['lastReadMessageId' => $messageId]]],
        ]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function the_first_message_of_a_conversation_is_emailed(): void
    {
        $this->captureSentMail();

        $this->sendMessage(3, 'Hello Bob', to: 4);

        $this->assertSame(1, $this->emailsTo('bob@machine.local'));
        $this->assertSame(0, $this->emailsTo('alice@machine.local'));
    }

    #[Test]
    public function more_messages_are_not_emailed_until_the_conversation_is_read(): void
    {
        $this->captureSentMail();

        [$dialogId] = $this->sendMessage(3, 'Hello Bob', to: 4);
        $this->sendMessage(3, 'Are you there?', $dialogId);
        $this->sendMessage(3, 'Bob?', $dialogId);

        $this->assertSame(1, $this->emailsTo('bob@machine.local'));
    }

    #[Test]
    public function once_the_conversation_is_read_the_next_message_is_emailed(): void
    {
        $this->captureSentMail();

        [$dialogId, $first] = $this->sendMessage(3, 'Hello Bob', to: 4);
        $this->readUpTo(4, $dialogId, $first);
        $this->sendMessage(3, 'Did you see my message?', $dialogId);

        $this->assertSame(2, $this->emailsTo('bob@machine.local'));
    }

    #[Test]
    public function reading_part_of_the_conversation_is_not_enough(): void
    {
        $this->captureSentMail();

        [$dialogId, $first] = $this->sendMessage(3, 'Hello Bob', to: 4);
        $this->sendMessage(3, 'Are you there?', $dialogId);
        $this->readUpTo(4, $dialogId, $first);
        $this->sendMessage(3, 'Bob?', $dialogId);

        // Bob still has "Are you there?" unread, so he's already been told.
        $this->assertSame(1, $this->emailsTo('bob@machine.local'));
    }

    #[Test]
    public function each_side_of_a_back_and_forth_is_emailed_once(): void
    {
        $this->captureSentMail();

        [$dialogId] = $this->sendMessage(3, 'Hello Bob', to: 4);
        // Replying marks what Bob replied to as read, so Alice is told about
        // his reply, but not about the next one until she reads it.
        $this->sendMessage(4, 'Hi Alice', $dialogId);
        $this->sendMessage(4, 'How are you?', $dialogId);
        $this->sendMessage(3, 'Good thanks!', $dialogId);

        $this->assertSame(1, $this->emailsTo('alice@machine.local'));
        $this->assertSame(2, $this->emailsTo('bob@machine.local'));
    }

    #[Test]
    public function a_message_read_before_the_queued_email_goes_out_is_not_emailed(): void
    {
        $this->prepareDatabase([
            Dialog::class => [
                ['id' => 102, 'type' => 'direct', 'last_message_id' => 102, 'created_at' => Carbon::now()],
            ],
            DialogMessage::class => [
                ['id' => 102, 'dialog_id' => 102, 'user_id' => 3, 'content' => '<t><p>Hello Bob</p></t>', 'number' => 1, 'created_at' => Carbon::now()],
            ],
            'dialog_user' => [
                ['dialog_id' => 102, 'user_id' => 3, 'joined_at' => Carbon::now(), 'last_read_message_id' => 102],
                // Bob opened the conversation before the worker reached the job.
                ['dialog_id' => 102, 'user_id' => 4, 'joined_at' => Carbon::now(), 'last_read_message_id' => 102],
            ],
        ]);

        $this->captureSentMail();

        $this->app()->getContainer()->call([new SendMessageNotificationsJob(DialogMessage::query()->find(102)), 'handle']);

        $this->assertSame(0, $this->emailsTo('bob@machine.local'));
    }
}
