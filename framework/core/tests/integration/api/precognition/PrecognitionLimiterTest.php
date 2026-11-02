<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\precognition;

require_once __DIR__.'/PrecognitionTestHelpers.php';

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The precognition limiter counts per user, or per REMOTE_ADDR for guests,
 * and can't be sidestepped through request headers.
 */
class PrecognitionLimiterTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use PrecognitionTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);

        $this->limitPrecognition(3);
    }

    protected function guestRegistration(string $ip): ServerRequestInterface
    {
        return $this->precognitive($this->jsonRequest('POST', '/api/users', ['data' => ['type' => 'users', 'attributes' => [
            'username' => 'bad name!',
            'email' => 'newcomer@machine.local',
            'password' => 'long-enough-password',
        ]]], null, $ip), 'username');
    }

    #[Test]
    public function guests_are_limited_per_ip_whatever_the_headers_say()
    {
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals(422, $this->send($this->guestRegistration('10.0.0.1'))->getStatusCode());
        }

        $this->assertEquals(429, $this->send($this->guestRegistration('10.0.0.1'))->getStatusCode());

        foreach (['X-Forwarded-For' => '1.2.3.4', 'X-Real-IP' => '1.2.3.5', 'Forwarded' => 'for=1.2.3.6', 'Client-IP' => '1.2.3.7'] as $header => $value) {
            $response = $this->send($this->guestRegistration('10.0.0.1')->withHeader($header, $value));
            $this->assertEquals(429, $response->getStatusCode(), "$header changed the limiter key");
        }

        $response = $this->send($this->guestRegistration('10.0.0.1')->withHeader('Precognition', 'TRUE')->withoutHeader('Precognition-Validate-Only'));
        $this->assertEquals(429, $response->getStatusCode());
        $this->assertEquals('true', $response->getHeaderLine('Precognition'));

        // Another address has its own budget.
        $this->assertEquals(422, $this->send($this->guestRegistration('10.0.0.2'))->getStatusCode());
    }

    #[Test]
    public function users_are_limited_per_user_across_ips_and_tokens()
    {
        $rename = fn (string $ip) => $this->precognitive($this->jsonRequest('PATCH', '/api/users/2', [
            'data' => ['type' => 'users', 'id' => '2', 'attributes' => ['username' => 'renamed']],
        ], 1, $ip));

        // Every precognitive request counts, whatever it answers.
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals(204, $this->send($rename("10.1.0.$i"))->getStatusCode());
        }

        // A new token (each request creates one) from a new address: still limited.
        $this->assertEquals(429, $this->send($rename('10.2.0.1'))->getStatusCode());

        // Normal requests are not counted and not limited by it.
        $this->assertEquals(200, $this->send($this->jsonRequest('PATCH', '/api/users/2', [
            'data' => ['type' => 'users', 'id' => '2', 'attributes' => ['username' => 'renamed']],
        ], 1))->getStatusCode());

        // A guest from the same address as the user's requests has a separate budget.
        $this->assertEquals(422, $this->send($this->guestRegistration('10.1.0.0'))->getStatusCode());
    }
}
