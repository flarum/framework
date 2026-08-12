<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\access_tokens;

use Flarum\Extend;
use Flarum\Http\AccessToken;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SessionAdminPayloadTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
        ]);
    }

    protected function payload(): array
    {
        $response = $this->send(
            $this->request('GET', '/admin', ['authenticatedAs' => 1])
        );

        $body = (string) $response->getBody();

        preg_match('/<script id="flarum-json-payload" type="application\/json">(.+?)<\/script>/s', $body, $matches);

        $this->assertNotEmpty($matches, 'The admin page carried no JSON payload.');

        return json_decode(html_entity_decode($matches[1]), true);
    }

    #[Test]
    public function payload_carries_the_session_lifetime()
    {
        $this->assertEquals(120, $this->payload()['sessionLifetime']);
    }

    #[Test]
    public function payload_carries_configurable_token_types()
    {
        $types = array_column($this->payload()['accessTokenLifetimes'], 'lifetime', 'type');

        $this->assertArrayHasKey('session', $types);
        $this->assertArrayHasKey('session_remember', $types);
        $this->assertEquals(60 * 60, $types['session']);
        $this->assertEquals(5 * 365 * 24 * 60 * 60, $types['session_remember']);
    }

    #[Test]
    public function payload_leaves_out_types_that_cannot_be_configured()
    {
        $types = array_column($this->payload()['accessTokenLifetimes'], 'type');

        $this->assertNotContains('developer', $types);
    }

    #[Test]
    public function payload_includes_a_type_added_by_an_extension()
    {
        $this->extend(
            (new Extend\AccessToken())
                ->type(PayloadTestToken::class)
        );

        $types = array_column($this->payload()['accessTokenLifetimes'], 'lifetime', 'type');

        $this->assertArrayHasKey('payload_test', $types);
        $this->assertEquals(450, $types['payload_test']);
    }

    #[Test]
    public function payload_says_nothing_is_pinned_when_config_is_silent()
    {
        $this->assertFalse($this->payload()['sessionByConfig']);
    }

    #[Test]
    public function payload_says_when_config_pins_the_values()
    {
        $this->config('session.lifetime', 30);

        $payload = $this->payload();

        $this->assertTrue($payload['sessionByConfig']);
        $this->assertEquals(30, $payload['sessionLifetime']);
    }

    #[Test]
    public function payload_reflects_a_configured_token_lifetime()
    {
        $this->config('session.tokens.session', 900);

        $types = array_column($this->payload()['accessTokenLifetimes'], 'lifetime', 'type');

        $this->assertEquals(900, $types['session']);
    }
}

class PayloadTestToken extends AccessToken
{
    public static string $type = 'payload_test';

    protected static int $lifetime = 450;
}
