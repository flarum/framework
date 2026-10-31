<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\tests\integration\api;

use Carbon\Carbon;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class ErasureConfirmationEmailTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-gdpr');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'moderator', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'user5', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'user5@machine.local', 'is_email_confirmed' => 1, 'joined_at' => Carbon::now(), 'last_seen_at' => Carbon::now()],
            ],
            Group::class => [
                ['id' => 4, 'name_singular' => 'mod', 'name_plural' => 'mods'],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 4],
            ],
            'group_permission' => [
                ['permission' => 'processErasure', 'group_id' => 4],
            ],
            'gdpr_erasure' => [
                ['id' => 2, 'user_id' => 5, 'verification_token' => '123abc', 'status' => 'user_confirmed', 'reason' => 'forget me', 'created_at' => Carbon::now(), 'user_confirmed_at' => Carbon::now()],
            ],
        ]);
    }

    /**
     * Swap in a transport that records every sent message, so we can inspect
     * the rendered email. Returns the array the captured messages land in.
     *
     * @return \ArrayObject<int, RawMessage>
     */
    private function captureSentMail(): \ArrayObject
    {
        $captured = new \ArrayObject();

        $transport = new class($captured) implements TransportInterface {
            public function __construct(private \ArrayObject $captured)
            {
            }

            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                $this->captured[] = $message;

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

        return $captured;
    }

    private function processAnonymization(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/user-erasure-requests/2', [
                'authenticatedAs' => 3,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'processorComment' => 'done',
                            'processedMode' => ErasureRequest::MODE_ANONYMIZATION,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function anonymization_confirmation_email_renders_with_shared_view_data()
    {
        $captured = $this->captureSentMail();

        $this->processAnonymization();

        // Find the completion email (sent to the user's original address).
        $email = null;
        foreach ($captured as $message) {
            if ($message instanceof Email) {
                foreach ($message->getTo() as $address) {
                    if ($address->getAddress() === 'user5@machine.local') {
                        $email = $message;
                        break 2;
                    }
                }
            }
        }

        $this->assertNotNull($email, 'Erasure completion email was not sent to the user.');

        $rendered = ((string) $email->getHtmlBody()).((string) $email->getTextBody());

        // The shared email layout/components render the recipient address in
        // their footer (via `$userEmail`) and the greeting (via `$username`).
        // Both only appear when the sender shares that view data — the bug in
        // flarum/framework#4774 was that the erasure job did not, so the layout
        // rendered with undefined variables.
        $this->assertStringContainsString('user5@machine.local', $rendered);
        $this->assertStringContainsString('user5', $rendered);
    }
}
