<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api\dialog_messages;

use Carbon\Carbon;
use Flarum\Extend;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

/**
 * Sending in a group needs only the sender's own right to send. A member who
 * can't reply, or can't be written to at all, doesn't stop the others talking;
 * the latter just isn't told.
 */
class GroupSendTest extends TestCase
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
                // Unconfirmed, so only the Guest group's permissions: she can't send messages.
                ['id' => 5, 'username' => 'carol', 'email' => 'carol@machine.local', 'is_email_confirmed' => 0],
                // Nobody may write to him; see NobodyMayMessageDave.
                ['id' => 6, 'username' => 'dave', 'email' => 'dave@machine.local', 'is_email_confirmed' => 1],
                ['id' => 7, 'username' => 'erin', 'email' => 'erin@machine.local', 'is_email_confirmed' => 1],
            ],
            'group_permission' => [
                ['permission' => 'dialog.sendMessageWithoutThrottle', 'group_id' => 3],
            ],
            // A group from yesterday, from before carol lost the right to send
            // and dave became someone nobody may write to.
            Dialog::class => [
                ['id' => 80, 'type' => 'group', 'first_message_id' => 80, 'last_message_id' => 80, 'last_message_at' => Carbon::now()->subDay(), 'created_at' => Carbon::now()->subDay()],
            ],
            DialogMessage::class => [
                ['id' => 80, 'dialog_id' => 80, 'user_id' => 3, 'content' => '<t><p>Hi all</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subDay()],
            ],
            'dialog_user' => [
                ['dialog_id' => 80, 'user_id' => 3, 'joined_at' => Carbon::now()->subDay(), 'last_read_message_id' => 80],
                ['dialog_id' => 80, 'user_id' => 4, 'joined_at' => Carbon::now()->subDay(), 'last_read_message_id' => 80],
                ['dialog_id' => 80, 'user_id' => 5, 'joined_at' => Carbon::now()->subDay(), 'last_read_message_id' => 80],
                ['dialog_id' => 80, 'user_id' => 6, 'joined_at' => Carbon::now()->subDay(), 'last_read_message_id' => 80],
            ],
        ]);

        $this->extend(
            (new Extend\Policy())->modelPolicy(User::class, NobodyMayMessageDave::class)
        );
    }

    protected function reply(int $actor, int $dialogId = 80): ResponseInterface
    {
        return $this->send(
            $this->request('POST', '/api/dialog-messages', [
                'authenticatedAs' => $actor,
                'json' => [
                    'data' => [
                        'type' => 'dialog-messages',
                        'attributes' => ['content' => 'Morning!'],
                        'relationships' => ['dialog' => ['data' => ['type' => 'dialogs', 'id' => (string) $dialogId]]],
                    ],
                ],
            ])
        );
    }

    protected function canSendMessageIn(int $actor, int $dialogId = 80): ?bool
    {
        $response = $this->send($this->request('GET', "/api/dialogs/$dialogId", ['authenticatedAs' => $actor]));

        return json_decode((string) $response->getBody(), true)['data']['attributes']['canSendMessage'] ?? null;
    }

    #[Test]
    public function a_member_can_reply_although_others_in_the_group_cannot_reply_or_be_written_to(): void
    {
        $this->assertTrue($this->canSendMessageIn(4));
        $this->assertEquals(201, $this->reply(4)->getStatusCode());
    }

    #[Test]
    public function a_member_who_cannot_send_cannot_reply_in_a_group_nobody_has_opened(): void
    {
        $this->assertFalse($this->canSendMessageIn(5));
        $this->assertEquals(403, $this->reply(5)->getStatusCode());
    }

    #[Test]
    public function someone_who_is_not_in_the_group_cannot_send_in_it(): void
    {
        $this->assertEquals(404, $this->reply(7)->getStatusCode());
    }

    #[Test]
    public function every_member_but_the_sender_and_whoever_nobody_may_write_to_is_emailed(): void
    {
        $this->captureSentMail();

        $this->assertEquals(201, $this->reply(3)->getStatusCode());

        $this->assertEquals(1, $this->emailsTo('bob@machine.local'));
        $this->assertEquals(1, $this->emailsTo('carol@machine.local'));
        $this->assertEquals(0, $this->emailsTo('dave@machine.local'));
        $this->assertEquals(0, $this->emailsTo('alice@machine.local'));
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
        return count(array_filter(
            $this->sent->getArrayCopy(),
            fn ($message) => $message instanceof Email && in_array($address, array_map(fn ($to) => $to->getAddress(), $message->getTo()), true)
        ));
    }
}

/**
 * Stands in for an extension ruling a member out, as flarum/gdpr does for an
 * anonymised account.
 */
class NobodyMayMessageDave extends AbstractPolicy
{
    public function message(User $actor, User $user): ?string
    {
        return $user->id === 6 ? $this->deny() : null;
    }
}
