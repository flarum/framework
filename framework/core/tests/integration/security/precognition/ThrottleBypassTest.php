<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\security\precognition;

require_once __DIR__.'/PrecognitionTestHelpers.php';

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The precognition limiter (60 per minute, per user or per guest IP) must
 * really trip, and must not be avoidable by changing headers.
 */
class ThrottleBypassTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use PrecognitionTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'taken', 'email' => 'taken@machine.local', 'is_email_confirmed' => 1, 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim'],
            ],
        ]);
    }

    protected function probeEmail(string $email, string $ip): ServerRequestInterface
    {
        return $this->precognitive($this->jsonRequest('POST', '/api/users', ['data' => ['type' => 'users', 'attributes' => [
            'username' => 'probe'.substr(md5($email), 0, 8),
            'email' => $email,
            'password' => 'long-enough-password',
        ]]], null, $ip), 'email');
    }

    #[Test]
    public function guest_validate_only_registration_is_limited_per_ip()
    {
        // A taken email answers 422, a free one 204, as the normal sign-up validation does.
        $this->assertEquals(422, $this->send($this->probeEmail('taken@machine.local', '10.0.0.1'))->getStatusCode());
        $this->assertEquals(204, $this->send($this->probeEmail('free@machine.local', '10.0.0.1'))->getStatusCode());

        for ($i = 2; $i < 60; $i++) {
            $this->send($this->probeEmail("free$i@machine.local", '10.0.0.1'));
        }

        $this->assertEquals(429, $this->send($this->probeEmail('taken@machine.local', '10.0.0.1'))->getStatusCode());

        // X-Forwarded-For / X-Real-IP / Forwarded are not trusted: still limited.
        foreach (['X-Forwarded-For' => '1.2.3.4', 'X-Real-IP' => '1.2.3.5', 'Forwarded' => 'for=1.2.3.6', 'Client-IP' => '1.2.3.7'] as $header => $value) {
            $response = $this->send($this->probeEmail('taken@machine.local', '10.0.0.1')->withHeader($header, $value));
            $this->assertEquals(429, $response->getStatusCode(), "$header bypassed the limiter");
        }

        // Spoofed method override, validate-only list, header casing: still limited.
        $this->assertEquals(429, $this->send($this->probeEmail('taken@machine.local', '10.0.0.1')->withHeader('Precognition', 'TRUE'))->getStatusCode());
        $this->assertEquals(429, $this->send($this->probeEmail('taken@machine.local', '10.0.0.1')->withoutHeader('Precognition-Validate-Only'))->getStatusCode());

        // A different REMOTE_ADDR has its own budget (by design: per-IP for guests).
        $this->assertEquals(422, $this->send($this->probeEmail('taken@machine.local', '10.0.0.2'))->getStatusCode());
    }

    #[Test]
    public function member_limit_follows_the_user_not_the_ip_or_token()
    {
        $rename = fn (string $ip) => $this->precognitive($this->jsonRequest('PATCH', '/api/users/2', [
            'data' => ['type' => 'users', 'id' => '2', 'attributes' => ['username' => 'taken']],
        ], 2, $ip));

        // Every request counts, whatever it answers.
        for ($i = 0; $i < 60; $i++) {
            $this->send($rename('10.1.0.'.($i % 250)));
        }

        // New token (requestAsUser creates one), new IP: still limited, since it is keyed by user id.
        $response = $this->send($rename('10.2.0.1'));
        $this->assertEquals(429, $response->getStatusCode(), (string) $response->getBody());

        // As a guest from a fresh IP, the guest budget is separate.
        $this->assertEquals(422, $this->send($this->probeEmail('taken@machine.local', '10.3.0.1'))->getStatusCode());
    }
}
